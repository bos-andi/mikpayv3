@php
    $telegramUrl = config('mikpay.telegram.group_url');
    $perks = [
        ['fa-rocket', 'Fitur baru lebih dulu', 'Jadi yang pertama tahu setiap pembaruan MikPay.'],
        ['fa-lightbulb', 'Tips MikroTik & billing', 'PPPoE, hotspot, isolir, sampai trik VPN remote.'],
        ['fa-server', 'Info status server', 'Kabar maintenance & gangguan langsung dari tim.'],
        ['fa-gift', 'Promo lebih dulu', 'Info promo & penawaran diumumkan pertama di grup.'],
    ];
    $messages = [
        ['Update Fitur', 'bg-indigo-50 text-indigo-700', 'Sekarang bisa tambah pelanggan PPP langsung dari panel — otomatis tersimpan di MikroTik.'],
        ['Tips', 'bg-amber-50 text-amber-700', 'Cek koneksi VPN remote cukup dengan perintah /interface ovpn-client print. Pastikan ada huruf R.'],
        ['Fitur Baru', 'bg-emerald-50 text-emerald-700', 'Penarikan dana hasil pembayaran Xendit kini bisa diajukan dari menu Laporan Keuangan.'],
    ];
@endphp

@if ($telegramUrl)
<section id="komunitas" class="py-16 lg:py-20 bg-slate-50 scroll-mt-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-[2rem] border border-sky-100 bg-white shadow-xl shadow-sky-500/10">
            <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-sky-100/70"></div>
            <div class="absolute -bottom-24 -left-16 w-72 h-72 rounded-full bg-cyan-50"></div>

            <div class="relative grid lg:grid-cols-2 gap-10 lg:gap-12 items-center p-6 sm:p-10 lg:p-14">
                <div>
                    <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-sky-700 bg-sky-50 border border-sky-100 px-3 py-1 rounded-full">
                        <i class="fa-brands fa-telegram"></i> Komunitas MikPay
                    </span>
                    <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Jangan sampai ketinggalan <span class="text-sky-600">kabar terbaru</span> MikPay.
                    </h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">
                        Gabung grup Telegram MikPay dan tumbuh bareng sesama pengelola ISP & RT RW Net.
                        Di sana kami berbagi update fitur, tips praktis, dan info penting lebih cepat dari kanal mana pun.
                    </p>

                    <ul class="mt-7 grid sm:grid-cols-2 gap-4">
                        @foreach ($perks as [$icon, $title, $desc])
                            <li class="flex items-start gap-3">
                                <span class="w-9 h-9 shrink-0 rounded-xl bg-sky-500/10 text-sky-600 flex items-center justify-center">
                                    <i class="fa-solid {{ $icon }} text-sm"></i>
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">{{ $title }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $desc }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-3">
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener"
                           class="group inline-flex items-center justify-center gap-2.5 bg-[#229ED9] hover:bg-[#1c8cc2] text-white font-extrabold text-sm px-7 py-4 rounded-2xl shadow-lg shadow-sky-500/30 hover:-translate-y-0.5 transition">
                            <i class="fa-brands fa-telegram text-lg"></i> Gabung Grup Telegram
                            <i class="fa-solid fa-arrow-right text-xs opacity-70 group-hover:translate-x-0.5 transition"></i>
                        </a>
                        <p class="text-xs text-slate-500 font-semibold">Gratis · Terbuka untuk semua · Bisa keluar kapan saja</p>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-md">
                    <div class="rounded-3xl bg-white border border-slate-200 shadow-2xl shadow-slate-900/10 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-4 bg-[#229ED9] text-white">
                            <img src="{{ asset(config('mikpay.brand.logo') ?? 'logo-mikpay.png') }}?v=2" alt="MikPay" class="w-10 h-10 rounded-full bg-white p-1 object-contain">
                            <div class="min-w-0">
                                <p class="font-bold text-sm truncate">Komunitas MikPay</p>
                                <p class="text-[11px] text-sky-100">grup publik · update & diskusi</p>
                            </div>
                            <i class="fa-brands fa-telegram text-2xl ml-auto opacity-80"></i>
                        </div>
                        <div class="p-4 space-y-3 bg-[linear-gradient(180deg,#f0f9ff,white)]">
                            @foreach ($messages as [$tag, $tagClass, $text])
                                <div class="max-w-[92%] rounded-2xl rounded-tl-md bg-white border border-slate-100 shadow-sm px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[11px] font-bold text-sky-700">MikPay Official</p>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $tagClass }}">{{ $tag }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-700 leading-relaxed">{{ $text }}</p>
                                </div>
                            @endforeach
                            <div class="ml-auto max-w-[70%] rounded-2xl rounded-tr-md bg-sky-500 text-white px-4 py-2.5 text-xs shadow-sm">
                                Mantap, langsung saya coba!
                            </div>
                        </div>
                        <a href="{{ $telegramUrl }}" target="_blank" rel="noopener" class="block text-center text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 py-3 border-t border-slate-100 transition">
                            Buka di Telegram <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
