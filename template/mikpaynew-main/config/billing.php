<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tanggal mulai pencatatan tagihan (tunggakan)
    |--------------------------------------------------------------------------
    |
    | Tunggakan dihitung per periode bulan (Y-m) mulai dari bulan ini,
    | BUKAN dari "mulai berlangganan" pelanggan di data PPP.
    |
    | Isi dengan tanggal saat Anda mulai memakai MIKPAY untuk catat tagihan
    | (mis. tanggal install / go-live), format Y-m-d.
    |
    | Prioritas: kolom business_settings.billing_tracking_start_date (DB),
    | lalu nilai di bawah (env), lalu fallback bulan berjalan saja.
    |
    */

    'tracking_start_date' => env('BILLING_TRACKING_START_DATE'),

];
