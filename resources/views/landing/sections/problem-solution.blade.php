@php
    $pairs = [
        ['Mencatat tagihan di buku atau Excel, sering lupa menagih.', 'Tagihan dibuat otomatis & dikirim via WhatsApp tiap bulan.', 'fa-file-invoice-dollar'],
        ['Isolir pelanggan menunggak satu per satu lewat Winbox.', 'Auto-isolir di MikroTik tepat di tanggal jatuh tempo, buka otomatis saat lunas.', 'fa-user-lock'],
        ['Tanpa IP Public, Winbox & OLT tidak bisa dibuka dari luar.', 'VPN remote gratis: Winbox, API, Web OLT & modem dari mana saja.', 'fa-shield-halved'],
        ['Teknisi bingung mencari lokasi ODP & jalur kabel.', 'Peta ODP & pelanggan di GIS, lengkap dengan titik koordinat.', 'fa-map-location-dot'],
    ];
@endphp

<section class="py-20 lg:py-24 bg-slate-50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">Kenapa MikPay</span>
            <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Tinggalkan cara lama yang menguras waktu & tenaga.</h2>
            <p class="mt-4 text-slate-600">Semua pekerjaan rutin ISP / RT RW Net kecil sampai menengah, dirapikan dalam satu panel.</p>
        </div>

        <div class="space-y-4 max-w-5xl mx-auto">
            <div class="hidden md:grid grid-cols-[1fr_auto_1fr] gap-6 px-2 text-[11px] font-bold uppercase tracking-widest">
                <span class="text-rose-500"><i class="fa-solid fa-xmark mr-1"></i> Cara lama</span>
                <span class="w-10"></span>
                <span class="text-indigo-600"><i class="fa-solid fa-check mr-1"></i> Dengan MikPay</span>
            </div>

            @foreach ($pairs as $pair)
                <div class="reveal grid md:grid-cols-[1fr_auto_1fr] gap-3 md:gap-6 items-center">
                    <div class="flex items-start gap-3 rounded-2xl bg-white border border-slate-200 p-4 sm:p-5 text-sm text-slate-500">
                        <i class="fa-solid fa-circle-xmark text-rose-400 mt-0.5"></i>
                        <span>{{ $pair[0] }}</span>
                    </div>
                    <span class="hidden md:flex w-10 h-10 rounded-full gradient-bg text-white items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </span>
                    <div class="flex items-start gap-3 rounded-2xl bg-white gradient-border p-4 sm:p-5 text-sm text-slate-800 font-semibold shadow-md shadow-indigo-500/5">
                        <span class="w-8 h-8 shrink-0 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs"><i class="fa-solid {{ $pair[2] }}"></i></span>
                        <span class="pt-1">{{ $pair[1] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
