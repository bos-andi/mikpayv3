<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\Router;
use App\Models\Tenant;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportService
{
    /** Urutan kolom template */
    public const COL_KEYS = [
        'customer_name',
        'display_name',
        'phone',
        'email',
        'zone',
        'monthly_fee',
        'due_day',
        'started_at',
        'address',
        'notes',
    ];

    public const HEADER_LABELS = [
        'Username PPP (MikroTik)',
        'Nama',
        'No. HP',
        'Email',
        'Zona',
        'Tarif per bulan (Rp)',
        'Tanggal jatuh tempo (1–31)',
        'Mulai layanan',
        'Alamat',
        'Catatan',
    ];

    /**
     * @param  array<int, array<string, mixed>>  $pppCustomers
     * @param  \Illuminate\Support\Collection<string, BillingCustomer>  $billingByName
     */
    public function buildTemplate(array $pppCustomers, $billingByName, string $routerLabel): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pelanggan');

        $sheet->setCellValue('A1', 'MIKPAY — Template data pelanggan / tagihan');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue(
            'A2',
            'Router: '.$routerLabel.' — Username PPP (kuning) jangan diubah. Kolom lain opsional. Mulai layanan kosong + ada jatuh tempo = otomatis hari ini (tagihan mulai bulan daftar, bukan mundur).'
        );
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true);
        $sheet->getRowDimension(2)->setRowHeight(36);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('334155');

        $headerRow = 3;
        foreach (self::HEADER_LABELS as $i => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$headerRow, $label);
        }
        $this->applyHeaderStyle($sheet, $headerRow, count(self::HEADER_LABELS));

        $row = 4;
        foreach ($pppCustomers as $ppp) {
            $name = (string) ($ppp['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $billing = $billingByName->get(strtolower($name));

            $sheet->setCellValueExplicit('A'.$row, $name, DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$row, $billing?->display_name ?? '');
            $phone = (string) ($billing?->phone ?? '');
            if ($phone !== '') {
                $sheet->setCellValueExplicit('C'.$row, $phone, DataType::TYPE_STRING);
            }
            $sheet->setCellValue('D'.$row, $billing?->email ?? '');
            $sheet->setCellValue('E'.$row, $billing?->zone ?? '');
            $fee = (int) ($billing?->monthly_fee ?? 0);
            $sheet->setCellValue('F'.$row, $fee > 0 ? $fee : '');
            $due = (int) ($billing?->due_day ?? 0);
            $sheet->setCellValue('G'.$row, $due > 0 ? $due : '');
            if ($billing?->started_at) {
                $sheet->setCellValue('H'.$row, ExcelDate::PHPToExcel($billing->started_at));
            }
            $sheet->setCellValue('I'.$row, $billing?->address ?? '');
            $sheet->setCellValue('J'.$row, $billing?->notes ?? '');
            $row++;
        }

        $lastDataRow = max(4, $row - 1);
        $this->applyDataFormats($sheet, 4, $lastDataRow);
        $this->applyUsernameYellowLock($sheet, 4, $lastDataRow);

        foreach (range(1, 10) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setWidth($c === 1 ? 24 : ($c === 9 ? 28 : 16));
        }
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:J3');

        // Lock username column; unlock other editable cells.
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('mikpay');
        $sheet->getProtection()->setSort(false);
        $sheet->getProtection()->setInsertRows(false);
        $sheet->getProtection()->setDeleteRows(false);

        return $spreadsheet;
    }

    public function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, true>  $allowedPppNames  lowercase name => true
     * @return array{saved: int, skipped: int, errors: list<string>}
     */
    public function importFromPath(string $path, Router $router, Tenant $tenant, array $allowedPppNames): array
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('Data Pelanggan') ?: $spreadsheet->getActiveSheet();

        $saved = 0;
        $skipped = 0;
        $errors = [];
        $highest = (int) $sheet->getHighestDataRow();

        for ($row = 4; $row <= $highest; $row++) {
            $username = trim((string) $sheet->getCell('A'.$row)->getValue());
            if ($username === '') {
                continue;
            }

            $key = strtolower($username);
            if (! isset($allowedPppNames[$key])) {
                $skipped++;
                $errors[] = "Baris {$row}: username “{$username}” tidak ada di PPP MikroTik aktif.";

                continue;
            }

            try {
                $displayName = $this->cellString($sheet, 'B'.$row);
                $phone = $this->cellString($sheet, 'C'.$row);
                $email = $this->cellString($sheet, 'D'.$row);
                $zone = $this->cellString($sheet, 'E'.$row);
                $fee = $this->cellInt($sheet, 'F'.$row);
                $dueDay = $this->cellInt($sheet, 'G'.$row);
                $startedAt = $this->cellDate($sheet, 'H'.$row);
                $address = $this->cellString($sheet, 'I'.$row);
                $notes = $this->cellString($sheet, 'J'.$row);

                if ($dueDay < 0) {
                    $dueDay = 0;
                }
                if ($dueDay > 31) {
                    $dueDay = 31;
                }
                if ($fee < 0) {
                    $fee = 0;
                }
                if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$row}: email “{$email}” tidak valid — dilewati, field lain tetap disimpan.";
                    $email = '';
                }

                $existing = BillingCustomer::query()
                    ->where('router_id', $router->id)
                    ->where('customer_name', $username)
                    ->first();

                // Mulai layanan kosong → pertahankan yang lama, atau hari ini jika baru + ada jatuh tempo.
                if ($startedAt === null && $existing?->started_at) {
                    $startedAt = $existing->started_at->format('Y-m-d');
                }
                if ($dueDay > 0 && $startedAt === null) {
                    $startedAt = now()->toDateString();
                }

                BillingCustomer::query()->updateOrCreate(
                    [
                        'router_id' => $router->id,
                        'customer_name' => $username,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'display_name' => $displayName !== '' ? $displayName : null,
                        'phone' => $phone !== '' ? $phone : null,
                        'email' => $email !== '' ? $email : null,
                        'zone' => $zone !== '' ? $zone : null,
                        'monthly_fee' => $fee,
                        'due_day' => $dueDay,
                        'started_at' => $startedAt,
                        'address' => $address !== '' ? $address : null,
                        'notes' => $notes !== '' ? $notes : null,
                    ]
                );
                $saved++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Baris {$row}: ".$e->getMessage();
            }
        }

        return compact('saved', 'skipped', 'errors');
    }

    private function applyHeaderStyle(Worksheet $sheet, int $row, int $colCount): void
    {
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $range = 'A'.$row.':'.$lastCol.$row;
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('CBD5E1');
    }

    private function applyDataFormats(Worksheet $sheet, int $firstRow, int $lastRow): void
    {
        if ($lastRow < $firstRow) {
            return;
        }
        $sheet->getStyle('A'.$firstRow.':A'.$lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('C'.$firstRow.':C'.$lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('F'.$firstRow.':F'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('H'.$firstRow.':H'.$lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        // Unlock editable columns B–J
        $sheet->getStyle('B'.$firstRow.':J'.$lastRow)->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);
    }

    private function applyUsernameYellowLock(Worksheet $sheet, int $firstRow, int $lastRow): void
    {
        if ($lastRow < $firstRow) {
            return;
        }
        $range = 'A'.$firstRow.':A'.$lastRow;
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FEF08A'); // yellow
        $sheet->getStyle($range)->getProtection()
            ->setLocked(Protection::PROTECTION_PROTECTED);
        $sheet->getStyle('A3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EAB308');
    }

    private function cellString(Worksheet $sheet, string $coord): string
    {
        $val = $sheet->getCell($coord)->getValue();
        if ($val === null) {
            return '';
        }

        return trim((string) $val);
    }

    private function cellInt(Worksheet $sheet, string $coord): int
    {
        $val = $sheet->getCell($coord)->getCalculatedValue();
        if ($val === null || $val === '') {
            return 0;
        }

        return (int) round((float) $val);
    }

    private function cellDate(Worksheet $sheet, string $coord): ?string
    {
        $cell = $sheet->getCell($coord);
        $val = $cell->getValue();
        if ($val === null || $val === '') {
            return null;
        }

        try {
            if (is_numeric($val)) {
                return ExcelDate::excelToDateTimeObject((float) $val)->format('Y-m-d');
            }
            $raw = trim((string) $val);
            if ($raw === '') {
                return null;
            }

            return \Carbon\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
