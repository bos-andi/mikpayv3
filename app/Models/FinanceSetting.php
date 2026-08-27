<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceSetting extends Model
{
    /**
     * Akun neraca yang diisi manual (selain kas/piutang/utang usaha/laba ditahan otomatis).
     *
     * @var array<string, string>
     */
    public const MANUAL_LINES = [
        // Aktiva lancar
        'persediaan' => 'Persediaan ONU / Material',
        'beban_dibayar_dimuka' => 'Beban Dibayar di Muka',
        // Aktiva tetap umum
        'tanah' => 'Tanah',
        'bangunan' => 'Bangunan',
        'akum_penyusutan_bangunan' => 'Akumulasi Penyusutan Bangunan',
        'inventaris' => 'Inventaris / Peralatan Kantor',
        'akum_penyusutan_inventaris' => 'Akumulasi Penyusutan Inventaris',
        'kendaraan' => 'Kendaraan',
        'akum_penyusutan_kendaraan' => 'Akumulasi Penyusutan Kendaraan',
        // Aktiva tetap ISP
        'aset_olt' => 'Aset OLT / Server',
        'akum_penyusutan_olt' => 'Akumulasi Penyusutan OLT',
        'aset_odc_odp' => 'Aset ODC / ODP / Cabinet',
        'akum_penyusutan_odc' => 'Akumulasi Penyusutan ODC/ODP',
        'aset_kabel' => 'Aset Kabel / Fiber Plant',
        'akum_penyusutan_kabel' => 'Akumulasi Penyusutan Kabel',
        // Kewajiban
        'utang_gaji' => 'Utang Gaji',
        'utang_pajak' => 'Utang Pajak',
        'beban_masih_harus_dibayar' => 'Beban yang Masih Harus Dibayar',
        'utang_bank_jp' => 'Utang Bank Jangka Panjang',
        // Ekuitas
        'prive' => 'Prive / Penarikan Modal',
    ];

    /** @var list<string> Contra-asset (dikurangi dari aktiva tetap) */
    public const CONTRA_ASSET_KEYS = [
        'akum_penyusutan_bangunan',
        'akum_penyusutan_inventaris',
        'akum_penyusutan_kendaraan',
        'akum_penyusutan_olt',
        'akum_penyusutan_odc',
        'akum_penyusutan_kabel',
    ];

    /**
     * Akun yang bisa ditambah via form "Belanja aset" (capex).
     *
     * @var array<string, string>
     */
    public const ASSET_PURCHASE_KEYS = [
        'persediaan' => 'Persediaan ONU / Material',
        'aset_olt' => 'Aset OLT / Server',
        'aset_odc_odp' => 'Aset ODC / ODP / Cabinet',
        'aset_kabel' => 'Aset Kabel / Fiber Plant',
        'inventaris' => 'Inventaris / Peralatan Kantor',
        'kendaraan' => 'Kendaraan',
        'bangunan' => 'Bangunan',
        'tanah' => 'Tanah',
        'beban_dibayar_dimuka' => 'Beban Dibayar di Muka',
    ];

    /** @var list<string> */
    public const CURRENT_ASSET_KEYS = [
        'persediaan',
        'beban_dibayar_dimuka',
    ];

    /** @var list<string> */
    public const FIXED_ASSET_KEYS = [
        'tanah',
        'bangunan',
        'akum_penyusutan_bangunan',
        'inventaris',
        'akum_penyusutan_inventaris',
        'kendaraan',
        'akum_penyusutan_kendaraan',
        'aset_olt',
        'akum_penyusutan_olt',
        'aset_odc_odp',
        'akum_penyusutan_odc',
        'aset_kabel',
        'akum_penyusutan_kabel',
    ];

    /** @var list<string> */
    public const LIABILITY_KEYS = [
        'utang_gaji',
        'utang_pajak',
        'beban_masih_harus_dibayar',
        'utang_bank_jp',
    ];

    protected $fillable = [
        'tenant_id',
        'router_id',
        'opening_cash',
        'opening_equity',
        'opening_as_of',
        'manual_lines',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'integer',
            'opening_equity' => 'integer',
            'opening_as_of' => 'date',
            'manual_lines' => 'array',
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

    public function manualAmount(string $key): int
    {
        $lines = $this->manual_lines ?? [];

        return (int) ($lines[$key] ?? 0);
    }
}
