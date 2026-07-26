<?php

namespace App\Services;

use App\Helpers\BillingStatusHelper;
use App\Models\BillingCustomer;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingCustomerTemplateService
{
    /** Urutan kolom data (baris 1 = header, baris 2+ = data) */
    public const COL_KEYS = [
        'customer_name',
        'display_name',
        'phone',
        'email',
        'zone',
        'package_label',
        'monthly_fee',
        'due_day',
        'started_at',
        'address',
        'notes',
    ];

    /** Label header formal (Indonesia) */
    private const HEADER_LABELS = [
        'Username PPP (MikroTik) *',
        'Nama tampilan',
        'No. HP',
        'Email',
        'Zona',
        'Label paket',
        'Tarif per bulan (Rp)',
        'Tanggal jatuh tempo (1–31) *',
        'Mulai layanan (tanggal)',
        'Alamat',
        'Catatan',
    ];

    /**
     * Bangun file XLSX berisi pelanggan PPP yang status tagihan "Belum Diatur" (due_day belum valid).
     *
     * @param  array<int, array<string, mixed>>  $pppSecrets  Output /ppp/secret/print
     * @param  \Illuminate\Support\Collection<string, \App\Models\BillingCustomer>  $billingByCustomer
     */
    public function buildExportNotSet(array $pppSecrets, $billingByCustomer): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pelanggan');

        $sheet->setCellValue('A1', 'MIKPAY — Template pengaturan tagihan WiFi');
        $spreadsheet->getActiveSheet()->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->setCellValue('A2', 'Isi data pada baris ke-4 dst. Kolom bertanda * wajib. Username PPP harus sama dengan PPP Secret di router — jangan diubah. Lihat lembar «Contoh pengisian» untuk contoh mengisi satu baris. Unggah kembali file ini setelah diisi.');
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(36);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('334155');

        $headerRow = 3;
        foreach (self::HEADER_LABELS as $i => $label) {
            $col = $i + 1;
            $cell = Coordinate::stringFromColumnIndex($col) . $headerRow;
            $sheet->setCellValue($cell, $label);
        }
        $this->applyHeaderStyle($sheet, $headerRow, 1, count(self::HEADER_LABELS));

        $row = 4;
        foreach ($pppSecrets as $secret) {
            if (($secret['disabled'] ?? 'false') === 'true') {
                continue;
            }
            $customerName = $secret['name'] ?? '';
            if ($customerName === '') {
                continue;
            }
            $billing = $billingByCustomer->get($customerName);
            $dueDay = $billing ? (int) $billing->due_day : 0;
            $status = BillingStatusHelper::getStatus($customerName, $dueDay);
            if (($status['status'] ?? '') !== 'not_set') {
                continue;
            }

            $sheet->setCellValue(Coordinate::stringFromColumnIndex(1) . $row, $customerName);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(2) . $row, $billing?->display_name ?? '');
            $phone = (string) ($billing?->phone ?? '');
            if ($phone !== '') {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex(3) . $row,
                    $phone,
                    DataType::TYPE_STRING
                );
            } else {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex(3) . $row, '');
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(4) . $row, $billing?->email ?? '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(5) . $row, $billing?->zone ?? '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(6) . $row, $billing?->package_label ?? '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(7) . $row, $billing && $billing->monthly_fee > 0 ? (int) $billing->monthly_fee : '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(8) . $row, '');
            if ($billing?->started_at) {
                $sheet->setCellValue(
                    Coordinate::stringFromColumnIndex(9) . $row,
                    Date::PHPToExcel($billing->started_at)
                );
            } else {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex(9) . $row, '');
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(10) . $row, $billing?->address ?? '');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(11) . $row, $billing?->notes ?? '');
            $row++;
        }

        $lastDataRow = max(4, $row - 1);
        $this->applyDataColumnFormats($sheet, 4, $lastDataRow);

        foreach (range(1, 11) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setWidth($c === 1 ? 22 : ($c === 10 ? 28 : 14));
        }
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:K3');

        $this->addContohPengisianSheet($spreadsheet);

        return $spreadsheet;
    }

    /**
     * Lembar terpisah berisi 1 baris contoh format pengisian (tidak dipakai saat import).
     */
    private function addContohPengisianSheet(Spreadsheet $spreadsheet): void
    {
        $ex = new Worksheet($spreadsheet, 'Contoh pengisian');
        $spreadsheet->addSheet($ex, 1);

        $ex->setCellValue('A1', 'MIKPAY — Contoh pengisian (1 baris)');
        $ex->mergeCells('A1:K1');
        $ex->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $ex->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $ex->setCellValue(
            'A2',
            'Baris di bawah ini hanya contoh format. Username «user.contoh» bersifat ilustrasi — saat mengunggah, isi data di lembar «Data Pelanggan» dengan username PPP yang benar dari router Anda. Lembar ini boleh dihapus sebelum unggah.'
        );
        $ex->mergeCells('A2:K2');
        $ex->getStyle('A2')->getAlignment()->setWrapText(true);
        $ex->getRowDimension(2)->setRowHeight(48);
        $ex->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('334155');

        $headerRow = 3;
        foreach (self::HEADER_LABELS as $i => $label) {
            $ex->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $label);
        }
        $this->applyHeaderStyle($ex, $headerRow, 1, count(self::HEADER_LABELS));

        $dataRow = 4;
        $ex->setCellValue(Coordinate::stringFromColumnIndex(1) . $dataRow, 'user.contoh');
        $ex->setCellValue(Coordinate::stringFromColumnIndex(2) . $dataRow, 'Sdr. Budi Santoso');
        $ex->setCellValueExplicit(
            Coordinate::stringFromColumnIndex(3) . $dataRow,
            '081234567890',
            DataType::TYPE_STRING
        );
        $ex->setCellValue(Coordinate::stringFromColumnIndex(4) . $dataRow, 'budi.santoso@email.com');
        $ex->setCellValue(Coordinate::stringFromColumnIndex(5) . $dataRow, 'Zona Timur');
        $ex->setCellValue(Coordinate::stringFromColumnIndex(6) . $dataRow, 'Paket 20 Mbps');
        $ex->setCellValue(Coordinate::stringFromColumnIndex(7) . $dataRow, 150000);
        $ex->setCellValue(Coordinate::stringFromColumnIndex(8) . $dataRow, 10);
        $ex->setCellValue(
            Coordinate::stringFromColumnIndex(9) . $dataRow,
            Date::PHPToExcel(new \DateTimeImmutable('2025-01-15'))
        );
        $ex->setCellValue(Coordinate::stringFromColumnIndex(10) . $dataRow, 'Jl. Melati No. 12, RT 03 / RW 02');
        $ex->setCellValue(Coordinate::stringFromColumnIndex(11) . $dataRow, 'Bayar via transfer; konfirmasi WA');

        $this->applyDataColumnFormats($ex, $dataRow, $dataRow);

        $range = 'A' . $dataRow . ':K' . $dataRow;
        $ex->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('ECFDF5');
        $ex->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('A7F3D0');
        $ex->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        foreach (range(1, 11) as $c) {
            $ex->getColumnDimensionByColumn($c)->setWidth($c === 1 ? 22 : ($c === 10 ? 30 : 14));
        }
        $ex->freezePane('A4');
    }

    private function applyHeaderStyle(Worksheet $sheet, int $row, int $colStart, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colStart + $colCount - 1);
        $range = 'A' . $row . ':' . $lastCol . $row;
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4D44B5');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('CBD5E1');
    }

    /**
     * Kolom C = teks (No. HP), G = tarif Rp, I = tanggal (tampilan dd/mm/yyyy).
     */
    private function applyDataColumnFormats(Worksheet $sheet, int $firstRow, int $lastRow): void
    {
        if ($lastRow < $firstRow) {
            return;
        }
        $sheet->getStyle('C' . $firstRow . ':C' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('G' . $firstRow . ':G' . $lastRow)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $sheet->getStyle('I' . $firstRow . ':I' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    }

    /**
     * Impor baris dari file XLSX. Hanya username yang ada di $allowedPppNames yang diproses.
     *
     * @param  array<string, true>  $allowedPppNames
     * @return array{saved: int, skipped: int, errors: array<int, string>}
     */
    public function importFromPath(string $path, array $allowedPppNames, ?string $routerSession): array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('Data Pelanggan') ?? $spreadsheet->getSheet(0);
        $highestRow = (int) $sheet->getHighestRow();
        $startDataRow = 4;

        $saved = 0;
        $skipped = 0;
        $errors = [];

        for ($r = $startDataRow; $r <= $highestRow; $r++) {
            $customerName = $this->stringCell($sheet, 1, $r);
            if ($customerName === '') {
                $skipped++;
                continue;
            }
            if (! isset($allowedPppNames[$customerName])) {
                $errors[] = "Baris {$r}: Username «{$customerName}» tidak ada di PPP Secret router ini.";
                continue;
            }

            $displayName = $this->stringCell($sheet, 2, $r);
            $phone = $this->phoneCell($sheet, $r);
            $email = $this->stringCell($sheet, 4, $r);
            $zone = $this->stringCell($sheet, 5, $r);
            $packageLabel = $this->stringCell($sheet, 6, $r);
            $monthlyFeeRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(7) . $r)->getValue();
            $dueDayRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(8) . $r)->getValue();
            $startedRaw = $sheet->getCell(Coordinate::stringFromColumnIndex(9) . $r)->getValue();
            $address = $this->stringCell($sheet, 10, $r);
            $notes = $this->stringCell($sheet, 11, $r);

            $dueDay = $this->parseInt($dueDayRaw);
            if ($dueDay === null || $dueDay < 1 || $dueDay > 31) {
                $errors[] = "Baris {$r}: Tanggal jatuh tempo harus angka 1–31 (isi sel kolom H).";
                continue;
            }

            $monthlyFee = $this->parseMoney($monthlyFeeRaw);

            $startedAt = $this->parseStartedAt($startedRaw);
            if ($startedRaw !== null && $startedRaw !== '' && $startedAt === null) {
                $errors[] = "Baris {$r}: Format tanggal mulai tidak valid (contoh: 15/01/2025 atau 2025-01-15).";
                continue;
            }

            $data = [
                'customer_name' => $customerName,
                'due_day' => $dueDay,
                'display_name' => $displayName !== '' ? $displayName : null,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'zone' => $zone !== '' ? $zone : null,
                'package_label' => $packageLabel !== '' ? $packageLabel : null,
                'monthly_fee' => $monthlyFee,
                'address' => $address !== '' ? $address : null,
                'notes' => $notes !== '' ? $notes : null,
                'started_at' => $startedAt,
                'router_session' => $routerSession,
            ];

            BillingCustomer::updateOrCreate(
                ['customer_name' => $customerName],
                $data
            );
            $saved++;
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function stringCell(Worksheet $sheet, int $col, int $row): string
    {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $v = $sheet->getCell($coord)->getValue();

        return is_string($v) ? trim($v) : trim((string) ($v ?? ''));
    }

    /**
     * No. HP: baca sebagai string agar angka 0 di depan tidak hilang (sel bertipe teks di Excel).
     */
    private function phoneCell(Worksheet $sheet, int $row): string
    {
        $coord = Coordinate::stringFromColumnIndex(3) . $row;
        $cell = $sheet->getCell($coord);
        $v = $cell->getValue();
        if ($v === null || $v === '') {
            return '';
        }
        if (is_string($v)) {
            return trim($v);
        }
        if (is_numeric($v)) {
            $f = (float) $v;

            return (string) (abs($f - round($f)) < 1e-9 ? (int) round($f) : $f);
        }

        return trim((string) $v);
    }

    private function parseStartedAt(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            try {
                return Date::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $s = trim((string) $raw);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
            $d = \DateTimeImmutable::createFromFormat('!' . $fmt, $s);
            if ($d instanceof \DateTimeImmutable) {
                return $d->format('Y-m-d');
            }
        }
        $ts = strtotime($s);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function parseInt(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (int) $raw;
        }
        $s = preg_replace('/\D/', '', (string) $raw);

        return $s !== '' ? (int) $s : null;
    }

    private function parseMoney(mixed $raw): int
    {
        if ($raw === null || $raw === '') {
            return 0;
        }
        if (is_numeric($raw)) {
            return max(0, (int) round((float) $raw));
        }
        $s = preg_replace('/[^\d]/', '', (string) $raw);

        return $s !== '' ? (int) $s : 0;
    }

    public function streamXlsxDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
