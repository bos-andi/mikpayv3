<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    public const TYPE_IN = 'masuk';

    public const TYPE_OUT = 'keluar';

    /** @var list<string> */
    public const CATEGORIES_IN = [
        'voucher',
        'instalasi',
        'lainnya',
    ];

    /** Beban operasional jaringan (HPP ISP). */
    public const EXPENSE_NETWORK = [
        'bandwidth',
        'listrik',
        'sewa',
        'material',
        'maintenance',
    ];

    /** Beban operasional umum. */
    public const EXPENSE_GENERAL = [
        'gaji',
        'transport',
        'lainnya',
    ];

    /**
     * Belanja modal (capex) — mengurangi kas & menambah aset neraca,
     * tidak masuk beban Laba Rugi.
     *
     * @var list<string>
     */
    public const CAPEX_OUT = [
        'belanja_aset',
    ];

    /** @var list<string> */
    public const CATEGORIES_OUT = [
        'bandwidth',
        'listrik',
        'sewa',
        'material',
        'maintenance',
        'gaji',
        'transport',
        'lainnya',
        'belanja_aset',
    ];

    protected $fillable = [
        'tenant_id',
        'router_id',
        'tanggal',
        'tipe',
        'kategori',
        'nominal',
        'metode',
        'keterangan',
        'ref_asset_key',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    public static function categoryLabel(string $kategori): string
    {
        return match ($kategori) {
            'voucher' => 'Pendapatan Voucher Hotspot',
            'instalasi' => 'Pendapatan Instalasi / Pasang Baru',
            'bandwidth' => 'Biaya Bandwidth / Uplink',
            'listrik' => 'Listrik POP / ODC',
            'gaji' => 'Gaji / Honor Teknisi & Admin',
            'material' => 'Material ODC / ODP / Kabel',
            'sewa' => 'Sewa Tower / Tiang / Lokasi',
            'transport' => 'Transport & Operasional Lapangan',
            'maintenance' => 'Maintenance & Perbaikan Jaringan',
            'belanja_aset' => 'Belanja Aset / Modal (Capex)',
            'lainnya' => 'Lain-lain',
            default => ucfirst($kategori),
        };
    }

    public static function isCapex(string $kategori): bool
    {
        return in_array($kategori, self::CAPEX_OUT, true);
    }
}
