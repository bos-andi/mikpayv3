@php
    $faqs = [
        [
            'id' => 'faq1',
            'q' => 'Apakah MikPay membutuhkan IP Public Statis di lokasi saya?',
            'a' => '<strong>Tidak sama sekali!</strong> MikPay menyediakan fasilitas Remote VPN Tunneling gratis. Router MikroTik Anda cukup dial VPN ke server Cloud MikPay, lalu Anda bisa mengakses Winbox & Web OLT dari mana saja.',
        ],
        [
            'id' => 'faq2',
            'q' => 'Apakah MikPay mendukung RouterOS v6 dan RouterOS v7?',
            'a' => '<strong>Ya, keduanya didukung penuh.</strong> Untuk RouterOS v7 kami merekomendasikan protokol WireGuard yang cepat dan hemat CPU. Untuk RouterOS v6 tersedia protokol SSTP.',
        ],
        [
            'id' => 'faq3',
            'q' => 'Bagaimana cara kerja fitur Auto-Isolir Pelanggan?',
            'a' => 'MikPay terhubung ke MikroTik via API. Saat pelanggan melewati tanggal jatuh tempo pembayaran, sistem akan otomatis mengubah Profile PPPoE atau men-disable secret pengguna tersebut secara real-time.',
        ],
        [
            'id' => 'faq4',
            'q' => 'Merk OLT apa saja yang didukung oleh MikPay?',
            'a' => 'MikPay mendukung hampir seluruh OLT EPON & GPON populer di Indonesia, seperti HSGQ, HIOSO, ZTE (C320/C300), VSOL, Huawei, dan V-MAX yang mendukung komunikasi SNMP / SSH / TR-069.',
        ],
    ];
@endphp

<section id="faq" class="py-20 bg-white border-t border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600 mb-2">Pertanyaan Umum</h2>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Sering Ditanyakan Pengguna Baru</h3>
        </div>

        <div class="space-y-4">
            @foreach ($faqs as $faq)
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm space-y-2">
                    <h4 class="font-bold text-sm text-slate-900 flex justify-between items-center cursor-pointer gap-4" onclick="toggleFaq('{{ $faq['id'] }}')">
                        <span>{{ $faq['q'] }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 shrink-0"></i>
                    </h4>
                    <p id="{{ $faq['id'] }}" class="text-xs text-slate-600 leading-relaxed pt-2 border-t border-slate-100">
                        {!! $faq['a'] !!}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
