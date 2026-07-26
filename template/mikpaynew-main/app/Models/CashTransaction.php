<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = [
        'tanggal',
        'tipe',
        'kategori',
        'nominal',
        'metode',
        'keterangan',
        'router_session',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
        ];
    }

    public static function kategoriMasuk(): array
    {
        return [
            'voucher' => 'Penjualan Voucher',
            'ppp' => 'Pembayaran Tagihan PPP',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function kategoriKeluar(): array
    {
        return [
            'listrik' => 'Listrik / Internet',
            'gaji' => 'Gaji / Komisi',
            'maintenance' => 'Maintenance',
            'pembelian' => 'Pembelian',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function metodeList(): array
    {
        return [
            'tunai' => 'Tunai',
            'transfer' => 'Transfer',
            'lainnya' => 'Lainnya',
        ];
    }

    public function getKategoriLabelAttribute(): string
    {
        $arr = $this->tipe === 'masuk' ? self::kategoriMasuk() : self::kategoriKeluar();

        return $arr[$this->kategori] ?? $this->kategori;
    }

    public function getMetodeLabelAttribute(): string
    {
        return self::metodeList()[$this->metode] ?? $this->metode;
    }

    /** Relasi ke BillingPayment saat source_type = ppp_payment */
    public function billingPayment()
    {
        return $this->belongsTo(BillingPayment::class, 'source_id');
    }

    /** Apakah transaksi dari pembayaran PPP (auto-sync) */
    public function isFromPppPayment(): bool
    {
        return $this->source_type === 'ppp_payment';
    }

    public function getCustomerNameLabelAttribute(): string
    {
        // Prioritas: relasi pembayaran PPP (source_id -> billing_payments)
        $nameFromRelation = trim((string) ($this->billingPayment?->display_name ?: $this->billingPayment?->customer_name ?: ''));
        if ($nameFromRelation !== '') {
            return $nameFromRelation;
        }

        // Fallback untuk data lama/manual: coba ekstrak dari keterangan "Pembayaran <nama> — ..."
        $note = trim((string) ($this->keterangan ?? ''));
        if ($note !== '' && preg_match('/^Pembayaran\s+(.+?)\s+—/u', $note, $m)) {
            $name = trim((string) ($m[1] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return '-';
    }
}
