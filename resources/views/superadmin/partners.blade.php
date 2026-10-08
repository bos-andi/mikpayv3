@extends('layouts.superadmin')

@section('title', 'Program Affiliate — Superadmin')
@section('heading', 'Program Affiliate')

@php
    $rp = fn (int $v) => 'Rp '.number_format($v, 0, ',', '.');
    $pct = fn (?float $v) => $v === null ? null : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    $pendingPartners = $partnersList->where('status', 'pending');
    $input = 'w-full rounded-lg border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:border-indigo-500';
@endphp

@section('content')
{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
    @foreach ([
        ['Affiliate aktif', number_format($summary['active']), 'fa-handshake', 'indigo'],
        ['Menunggu persetujuan', number_format($summary['pending']), 'fa-hourglass-half', 'amber'],
        ['ISP rujukan', number_format($summary['referrals']), 'fa-users', 'cyan'],
        ['Total komisi', $rp($summary['commission_total']), 'fa-coins', 'emerald'],
        ['Sudah dibayar', $rp($summary['paid_out']), 'fa-building-columns', 'slate'],
        ['Kewajiban komisi', $rp(max(0, $summary['owed'])), 'fa-scale-balanced', 'rose'],
    ] as $s)
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-{{ $s[3] }}-50 text-{{ $s[3] }}-600 flex items-center justify-center text-xs"><i class="fa-solid {{ $s[2] }}"></i></span>
                <p class="text-[11px] font-semibold text-slate-500 leading-tight">{{ $s[0] }}</p>
            </div>
            <p class="mt-2 text-lg font-extrabold text-slate-900">{{ $s[1] }}</p>
        </div>
    @endforeach
</div>

<div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
    <a href="{{ route('partner.landing') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 font-bold text-slate-700 hover:border-indigo-300"><i class="fa-solid fa-arrow-up-right-from-square"></i> Halaman publik affiliate</a>
    <button type="button" onclick="document.getElementById('addPartnerCard').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700"><i class="fa-solid fa-user-plus"></i> Tambah Affiliate</button>
    <button type="button" onclick="document.getElementById('partnerSettingsCard').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 font-bold text-slate-700 hover:border-indigo-300"><i class="fa-solid fa-sliders"></i> Pengaturan Komisi</button>
    @unless ($settings['enabled'])
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 font-bold text-rose-700"><i class="fa-solid fa-power-off"></i> Program affiliate nonaktif</span>
    @endunless
</div>

{{-- Add partner --}}
<div id="addPartnerCard" class="{{ $errors->hasAny(['name', 'email', 'password', 'code']) ? '' : 'hidden' }} mt-4 rounded-2xl bg-white border border-slate-200 p-5">
    <h2 class="text-sm font-extrabold text-slate-900">Tambah Affiliate Langsung</h2>
    <p class="text-[11px] text-slate-500">Untuk tim marketing internal. Akun langsung aktif tanpa proses persetujuan.</p>
    <form method="POST" action="{{ route('superadmin.partners.store') }}" class="mt-4 grid sm:grid-cols-3 gap-3">
        @csrf
        <input name="name" value="{{ old('name') }}" required placeholder="Nama lengkap" class="{{ $input }}">
        <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email login" class="{{ $input }}">
        <input name="phone" value="{{ old('phone') }}" placeholder="WhatsApp" class="{{ $input }}">
        <input name="city" value="{{ old('city') }}" placeholder="Kota" class="{{ $input }}">
        <input name="password" type="text" required minlength="8" placeholder="Password awal (min. 8)" class="{{ $input }}">
        <input name="code" value="{{ old('code') }}" maxlength="20" placeholder="Kode referral (kosong = otomatis)" class="{{ $input }} uppercase font-mono">
        <div class="sm:col-span-3">
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700"><i class="fa-solid fa-check"></i> Buat Akun Affiliate</button>
        </div>
    </form>
</div>

{{-- Settings --}}
<div id="partnerSettingsCard" class="hidden mt-4 rounded-2xl bg-white border border-slate-200 p-5">
    <h2 class="text-sm font-extrabold text-slate-900">Pengaturan Komisi Default</h2>
    <p class="text-[11px] text-slate-500">Berlaku untuk semua affiliate, kecuali affiliate yang punya tarif khusus.</p>
    <form method="POST" action="{{ route('superadmin.partners.settings') }}" class="mt-4 grid sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
        @csrf
        @foreach ([
            ['first_percent', 'Komisi pertama (%)', 0.01],
            ['recurring_percent', 'Komisi perpanjangan (%)', 0.01],
            ['recurring_months', 'Durasi perpanjangan (bulan)', 1],
            ['hold_days', 'Masa tahan (hari)', 1],
            ['min_withdrawal', 'Min. pencairan (Rp)', 1000],
            ['cookie_days', 'Masa cookie (hari)', 1],
            ['payout_day', 'Tanggal pencairan (1–28)', 1],
            ['referral_discount_percent', 'Diskon pelanggan baru (%)', 0.01],
        ] as [$field, $label, $step])
            <label class="block">
                <span class="block font-bold text-slate-600 mb-1">{{ $label }}</span>
                <input type="number" name="{{ $field }}" step="{{ $step }}" value="{{ $settings[$field] }}" required class="{{ $input }}">
            </label>
        @endforeach
        <label class="sm:col-span-3 lg:col-span-6 inline-flex items-center gap-2 font-semibold text-slate-700">
            <input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) class="rounded border-slate-300 text-indigo-600">
            Program affiliate aktif (komisi baru tercatat & link referral berfungsi)
        </label>
        <div class="sm:col-span-3 lg:col-span-6">
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-bold hover:bg-slate-800"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
            <span class="ml-2 text-[11px] text-slate-400">Durasi perpanjangan 0 = komisi hanya dari pembayaran pertama. Tanggal pencairan 0 = tanpa jadwal tetap. Diskon pelanggan baru = potongan pembayaran paket pertama untuk yang daftar pakai kode referral (0 = tanpa diskon).</span>
        </div>
    </form>
</div>

{{-- Pending applications --}}
@if ($pendingPartners->isNotEmpty())
    <div class="mt-5 rounded-2xl bg-white border border-amber-200 overflow-hidden">
        <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 flex items-center gap-2">
            <i class="fa-solid fa-hourglass-half text-amber-600"></i>
            <h2 class="text-sm font-extrabold text-amber-900">Pendaftaran Affiliate Baru ({{ $pendingPartners->count() }})</h2>
        </div>
        @foreach ($pendingPartners as $p)
            <div class="px-5 py-4 border-b border-slate-100 last:border-0 grid lg:grid-cols-12 gap-4">
                <div class="lg:col-span-7 text-xs">
                    <p class="text-sm font-bold text-slate-900">{{ $p->user?->name }}
                        @if ($p->user?->hasVerifiedEmail())
                            <span class="ml-1 align-middle inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold"><i class="fa-solid fa-envelope-circle-check"></i> Email terverifikasi</span>
                        @else
                            <span class="ml-1 align-middle inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 text-[10px] font-bold"><i class="fa-solid fa-envelope"></i> Email belum diverifikasi</span>
                        @endif
                    </p>
                    <p class="text-slate-500">{{ $p->user?->email }} · <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $p->phone)) }}" target="_blank" class="text-emerald-600 font-semibold hover:underline"><i class="fa-brands fa-whatsapp"></i> {{ $p->phone }}</a> · {{ $p->city }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">Daftar {{ format_wib($p->created_at) }}</p>
                    @if ($p->promotion_plan)
                        <p class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700 whitespace-pre-line">{{ $p->promotion_plan }}</p>
                    @endif
                </div>
                <div class="lg:col-span-5 flex flex-col gap-2">
                    @if ($p->user?->hasVerifiedEmail())
                        <form method="POST" action="{{ route('superadmin.partners.approve', $p) }}" class="flex flex-wrap items-center gap-1">
                            @csrf
                            <input name="code" maxlength="20" placeholder="Kode (kosong = otomatis)" class="flex-1 min-w-[140px] rounded-lg border border-slate-200 px-2 py-2 text-xs uppercase font-mono">
                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"><i class="fa-solid fa-check"></i> Setujui</button>
                        </form>
                    @else
                        <p class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-[11px] text-amber-800">
                            <i class="fa-solid fa-circle-info mr-1"></i> Belum bisa disetujui — calon affiliate harus klik tautan verifikasi di email dulu.
                        </p>
                    @endif
                    <form method="POST" action="{{ route('superadmin.partners.reject', $p) }}" class="flex flex-wrap items-center gap-1">
                        @csrf
                        <input name="reason" required maxlength="500" placeholder="Alasan tolak" class="flex-1 min-w-[140px] rounded-lg border border-slate-200 px-2 py-2 text-xs">
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700"><i class="fa-solid fa-xmark"></i> Tolak</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Pending withdrawals --}}
<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    @php $pendingTotal = (int) $pendingWithdrawals->sum('amount'); @endphp
    <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center gap-x-4 gap-y-2">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-money-bill-transfer text-indigo-600"></i>
            <h2 class="text-sm font-extrabold text-slate-900">Pencairan Komisi Menunggu ({{ $pendingWithdrawals->count() }})</h2>
        </div>
        <span class="text-[11px] text-slate-500">
            <i class="fa-regular fa-calendar"></i>
            {{ $nextPayoutDate ? 'Jadwal pencairan berikutnya: '.$nextPayoutDate->translatedFormat('d F Y') : 'Tanpa jadwal tetap' }}
        </span>
        @if ($pendingWithdrawals->isNotEmpty())
            <div class="ml-auto flex flex-wrap items-center gap-2 text-[11px]">
                <span class="text-slate-500">Total <b class="text-slate-900">{{ $rp($pendingTotal) }}</b></span>
                <span class="px-2 py-1 rounded-lg border {{ $xenditBalance !== null && $xenditBalance < $pendingTotal ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                    Saldo Xendit: <b>{{ $xenditBalance !== null ? $rp($xenditBalance) : 'tidak terbaca' }}</b>
                </span>
                @if ($xenditReady)
                    <form method="POST" action="{{ route('superadmin.partners.withdrawals.send-all') }}"
                          onsubmit="return MikPay.confirmForm(event, 'Kirim {{ $pendingWithdrawals->count() }} pencairan (total {{ $rp($pendingTotal) }}) ke rekening affiliate via Xendit sekarang? Dana Xendit akan langsung terpotong.', { title: 'Kirim Semua via Xendit', confirmText: 'Ya, kirim semua' })">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700"><i class="fa-solid fa-paper-plane"></i> Kirim Semua via Xendit</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
    @forelse ($pendingWithdrawals as $w)
        <div class="px-5 py-4 border-b border-slate-100 last:border-0 grid lg:grid-cols-12 gap-4 items-start">
            <div class="lg:col-span-6 text-xs">
                <p class="text-sm font-bold text-slate-900">{{ $w->partner?->user?->name }} <span class="font-mono text-[11px] text-indigo-600">{{ $w->partner?->code }}</span></p>
                <p class="text-slate-500"><span class="font-mono">{{ $w->reference }}</span> · {{ format_wib($w->created_at) }}</p>
                <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 inline-block">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $w->bank_name }}</p>
                    <p class="font-mono text-sm font-bold text-slate-900">{{ $w->account_number }}</p>
                    <p class="text-slate-600">a.n. {{ $w->account_holder }}</p>
                </div>
                @if ($w->note)
                    <p class="mt-1 text-slate-500 italic">"{{ $w->note }}"</p>
                @endif
            </div>
            <div class="lg:col-span-2">
                <p class="text-[10px] text-slate-500 font-semibold">Nominal transfer</p>
                <p class="text-lg font-extrabold text-slate-900">{{ $rp($w->amount) }}</p>
            </div>
            <div class="lg:col-span-4 flex flex-col gap-2">
                @if ($w->admin_note)
                    <p class="text-[10px] text-rose-600">{{ $w->admin_note }}</p>
                @endif
                @if ($xenditReady)
                    <form method="POST" action="{{ route('superadmin.partners.withdrawals.xendit', $w) }}"
                          onsubmit="return MikPay.confirmForm(event, 'Kirim {{ $rp($w->amount) }} ke {{ $w->bank_code }} {{ $w->account_number }} a.n. {{ addslashes($w->account_holder) }} via Xendit?', { title: 'Kirim via Xendit', confirmText: 'Kirim' })">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700"><i class="fa-solid fa-paper-plane"></i> Kirim via Xendit</button>
                    </form>
                @endif
                <details>
                    <summary class="cursor-pointer text-[11px] font-bold text-slate-500">Sudah transfer manual?</summary>
                    <form method="POST" action="{{ route('superadmin.partners.withdrawals.complete', $w) }}" class="mt-1 flex flex-wrap items-center gap-1"
                          onsubmit="return MikPay.confirmForm(event, 'Tandai {{ $w->reference }} sudah ditransfer manual {{ $rp($w->amount) }}?', { title: 'Konfirmasi Transfer Manual', confirmText: 'Sudah ditransfer' })">
                        @csrf
                        <input name="transfer_reference" maxlength="120" placeholder="No. referensi (opsional)" class="flex-1 min-w-[150px] rounded-lg border border-slate-200 px-2 py-2 text-xs">
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"><i class="fa-solid fa-check"></i> Tandai Selesai</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('superadmin.partners.withdrawals.reject', $w) }}" class="flex flex-wrap items-center gap-1">
                    @csrf
                    <input name="reason" required maxlength="500" placeholder="Alasan tolak" class="flex-1 min-w-[150px] rounded-lg border border-slate-200 px-2 py-2 text-xs">
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700"><i class="fa-solid fa-xmark"></i> Tolak</button>
                </form>
            </div>
        </div>
    @empty
        <p class="px-5 py-6 text-center text-xs text-slate-400">Tidak ada pencairan yang menunggu.</p>
    @endforelse

    @if ($processingWithdrawals->isNotEmpty())
        <div class="border-t border-slate-100 bg-indigo-50/40 px-5 py-3">
            <p class="text-[11px] font-bold text-indigo-800 mb-2"><i class="fa-solid fa-spinner"></i> Sedang ditransfer Xendit ({{ $processingWithdrawals->count() }})</p>
            <div class="space-y-1.5">
                @foreach ($processingWithdrawals as $w)
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-mono text-slate-500">{{ $w->reference }}</span>
                        <span class="font-bold">{{ $w->partner?->user?->name }}</span>
                        <span>{{ $rp($w->amount) }} → {{ $w->bank_code }} {{ $w->account_number }}</span>
                        <span class="text-[10px] text-slate-500">Xendit: {{ $w->xendit_status ?: '—' }}</span>
                        <form method="POST" action="{{ route('superadmin.partners.withdrawals.refresh', $w) }}">
                            @csrf
                            <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:underline"><i class="fa-solid fa-rotate"></i> Cek Status</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Pending bank verification --}}
<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2">
        <i class="fa-solid fa-id-card text-indigo-600"></i>
        <h2 class="text-sm font-extrabold text-slate-900">Verifikasi Rekening Affiliate ({{ $pendingBanks->count() }})</h2>
    </div>
    @forelse ($pendingBanks as $p)
        <div class="px-5 py-4 border-b border-slate-100 last:border-0 grid lg:grid-cols-12 gap-4 items-start">
            <div class="lg:col-span-4 grid grid-cols-2 gap-2">
                @foreach ([
                    ['Buku tabungan', 'fa-book', $p->bank_proof_path, $p->bankProofIsPdf(), route('superadmin.partners.bank.proof', $p)],
                    ['KTP', 'fa-id-card', $p->ktp_path, $p->ktpIsPdf(), route('superadmin.partners.bank.ktp', $p)],
                ] as [$docLabel, $docIcon, $docPath, $docPdf, $docUrl])
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1"><i class="fa-solid {{ $docIcon }} mr-0.5"></i> {{ $docLabel }}</p>
                        @if ($docPath)
                            <a href="{{ $docUrl }}" target="_blank" class="block h-28 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 hover:ring-2 hover:ring-indigo-300 transition">
                                @if ($docPdf)
                                    <span class="h-full flex flex-col items-center justify-center text-rose-600 text-xs font-bold"><i class="fa-solid fa-file-pdf text-3xl mb-1"></i>Lihat PDF</span>
                                @else
                                    <img src="{{ $docUrl }}" alt="{{ $docLabel }}" class="w-full h-full object-cover">
                                @endif
                            </a>
                        @else
                            <span class="h-28 flex flex-col items-center justify-center rounded-xl border border-dashed border-amber-300 bg-amber-50 text-amber-700 text-[11px] font-bold text-center px-2">
                                <i class="fa-solid fa-triangle-exclamation mb-1"></i> Belum diunggah
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="lg:col-span-4 text-xs">
                <p class="font-semibold text-slate-800">{{ $p->user?->name }} <span class="font-mono text-indigo-600">{{ $p->code }}</span></p>
                <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $p->bank_name ?: $p->bank_code }}</p>
                    <p class="font-mono text-sm font-bold text-slate-900">{{ $p->account_number }}</p>
                    <p class="text-slate-600">a.n. {{ $p->account_holder }}</p>
                </div>
                <p class="mt-2 text-[11px] text-slate-500 leading-relaxed">Cek: nama di buku tabungan, KTP, dan akun affiliate harus sama. Nomor rekening sesuai buku tabungan.</p>
            </div>
            <div class="lg:col-span-4 flex flex-col gap-2">
                @if ($p->bank_proof_path && $p->ktp_path)
                    <form method="POST" action="{{ route('superadmin.partners.bank.verify', $p) }}"
                          onsubmit="return MikPay.confirmForm(event, 'Verifikasi rekening {{ $p->bank_code }} {{ $p->account_number }} a.n. {{ addslashes((string) $p->account_holder) }}? Pastikan nama sesuai buku tabungan dan KTP.', { title: 'Verifikasi Rekening', confirmText: 'Ya, sesuai' })">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700"><i class="fa-solid fa-check"></i> Setujui Rekening</button>
                    </form>
                @else
                    <p class="text-[11px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">Dokumen belum lengkap — tolak dan minta affiliate mengunggah ulang.</p>
                @endif
                <form method="POST" action="{{ route('superadmin.partners.bank.reject', $p) }}" class="flex flex-wrap items-center gap-1">
                    @csrf
                    <input name="reason" required maxlength="500" placeholder="Alasan tolak (mis. nama tidak sesuai)" class="flex-1 min-w-[160px] rounded-lg border border-slate-200 px-2 py-2 text-xs">
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-rose-600 text-white text-xs font-bold hover:bg-rose-700"><i class="fa-solid fa-xmark"></i> Tolak</button>
                </form>
            </div>
        </div>
    @empty
        <p class="px-5 py-6 text-center text-xs text-slate-400">Tidak ada rekening yang menunggu verifikasi.</p>
    @endforelse
</div>

{{-- Partners table --}}
<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-extrabold text-slate-900">Daftar Affiliate</h2>
        <div class="flex gap-1 text-[11px] font-bold">
            @foreach (['' => 'Semua', 'active' => 'Aktif', 'pending' => 'Menunggu', 'suspended' => 'Nonaktif', 'rejected' => 'Ditolak'] as $key => $label)
                <a href="{{ route('superadmin.partners', $key !== '' ? ['status' => $key] : []) }}" class="px-2.5 py-1 rounded-lg {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2.5 text-left">Affiliate</th>
                    <th class="px-4 py-2.5 text-left">Kode</th>
                    <th class="px-4 py-2.5 text-left">Status</th>
                    <th class="px-4 py-2.5 text-right">Klik</th>
                    <th class="px-4 py-2.5 text-right">Rujukan</th>
                    <th class="px-4 py-2.5 text-right">Komisi</th>
                    <th class="px-4 py-2.5 text-right">Saldo</th>
                    <th class="px-4 py-2.5 text-left">Tarif</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($partnersList as $p)
                    @php $bal = $balances[$p->id]; @endphp
                    <tr class="hover:bg-slate-50/60 align-top">
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-900">{{ $p->user?->name }}</p>
                            <p class="text-[10px] text-slate-500">{{ $p->user?->email }}</p>
                            <p class="text-[10px] text-slate-400">{{ $p->phone }}{{ $p->city ? ' · '.$p->city : '' }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ $p->code ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $p->statusBadgeClass() }}">{{ $p->statusLabel() }}</span></td>
                        <td class="px-4 py-3 text-right">{{ number_format($p->clicks) }}</td>
                        <td class="px-4 py-3 text-right font-bold">{{ number_format($p->tenants_count) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700">{{ $rp((int) $p->commission_total) }}</td>
                        <td class="px-4 py-3 text-right">
                            <p class="font-bold">{{ $rp($bal['available']) }}</p>
                            @if ($bal['on_hold'] > 0)<p class="text-[10px] text-amber-600">+{{ $rp($bal['on_hold']) }} tertahan</p>@endif
                        </td>
                        <td class="px-4 py-3 text-[11px]">
                            {{ $pct($p->first_percent) ?? $pct($settings['first_percent']) }}% / {{ $pct($p->recurring_percent) ?? $pct($settings['recurring_percent']) }}%
                            @if ($p->first_percent !== null || $p->recurring_percent !== null)<span class="block text-[10px] text-violet-600 font-bold">khusus</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (in_array($p->status, ['active', 'suspended'], true))
                                <button type="button" onclick="document.getElementById('editPartner{{ $p->id }}').classList.toggle('hidden')" class="text-[11px] font-bold text-indigo-600 hover:underline">Edit</button>
                            @endif
                        </td>
                    </tr>
                    @if (in_array($p->status, ['active', 'suspended'], true))
                        <tr id="editPartner{{ $p->id }}" class="hidden bg-slate-50">
                            <td colspan="9" class="px-4 py-4">
                                <form method="POST" action="{{ route('superadmin.partners.update', $p) }}" class="grid sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                    @csrf
                                    @method('PUT')
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">Kode referral</span>
                                        <input name="code" value="{{ $p->code }}" required maxlength="20" class="{{ $input }} uppercase font-mono"></label>
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">Status</span>
                                        <select name="status" class="{{ $input }}">
                                            <option value="active" @selected($p->status === 'active')>Aktif</option>
                                            <option value="suspended" @selected($p->status === 'suspended')>Nonaktifkan</option>
                                        </select></label>
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">Komisi pertama (%)</span>
                                        <input name="first_percent" type="number" step="0.01" min="0" max="100" value="{{ $p->first_percent }}" placeholder="Default {{ $pct($settings['first_percent']) }}" class="{{ $input }}"></label>
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">Komisi perpanjangan (%)</span>
                                        <input name="recurring_percent" type="number" step="0.01" min="0" max="100" value="{{ $p->recurring_percent }}" placeholder="Default {{ $pct($settings['recurring_percent']) }}" class="{{ $input }}"></label>
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">WhatsApp</span>
                                        <input name="phone" value="{{ $p->phone }}" maxlength="30" class="{{ $input }}"></label>
                                    <label class="block"><span class="block text-[10px] font-bold text-slate-500 mb-1">Catatan admin</span>
                                        <input name="admin_note" value="{{ $p->admin_note }}" maxlength="1000" class="{{ $input }}"></label>
                                    <div class="sm:col-span-3 lg:col-span-6 flex items-center gap-3">
                                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700">Simpan</button>
                                        <span class="text-[10px] text-slate-400">Kosongkan tarif untuk memakai default. Mengganti kode membuat link lama tidak berlaku.</span>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Belum ada affiliate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent commissions --}}
<div class="mt-5 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100">
        <h2 class="text-sm font-extrabold text-slate-900">Komisi Terbaru</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2.5 text-left">Tanggal</th>
                    <th class="px-4 py-2.5 text-left">Affiliate</th>
                    <th class="px-4 py-2.5 text-left">Pelanggan</th>
                    <th class="px-4 py-2.5 text-left">Jenis</th>
                    <th class="px-4 py-2.5 text-right">Dasar</th>
                    <th class="px-4 py-2.5 text-right">Komisi</th>
                    <th class="px-4 py-2.5 text-left">Status</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($recentCommissions as $c)
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ format_wib($c->created_at) }}</td>
                        <td class="px-4 py-3"><span class="font-bold">{{ $c->partner?->user?->name }}</span> <span class="font-mono text-[10px] text-indigo-600">{{ $c->partner?->code }}</span></td>
                        <td class="px-4 py-3">{{ $c->tenant_name }}</td>
                        <td class="px-4 py-3">{{ $c->kindLabel() }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $rp($c->base_amount) }} × {{ $pct($c->percent) }}%</td>
                        <td class="px-4 py-3 text-right font-extrabold whitespace-nowrap {{ $c->isCancelled() ? 'line-through text-slate-400' : '' }}">{{ $rp($c->amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $c->statusBadgeClass() }}">{{ $c->statusLabel() }}</span>
                            @if ($c->cancel_reason)<p class="text-[10px] text-rose-500 mt-0.5">{{ $c->cancel_reason }}</p>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @unless ($c->isCancelled())
                                <details class="inline-block text-left">
                                    <summary class="cursor-pointer text-[11px] font-bold text-rose-600 list-none">Batalkan</summary>
                                    <form method="POST" action="{{ route('superadmin.partners.commissions.cancel', $c) }}" class="mt-1 flex gap-1">
                                        @csrf
                                        <input name="reason" required maxlength="500" placeholder="Alasan (mis. refund)" class="w-40 rounded-lg border border-slate-200 px-2 py-1.5 text-[11px]">
                                        <button type="submit" class="px-2 py-1.5 rounded-lg bg-rose-600 text-white text-[11px] font-bold">OK</button>
                                    </form>
                                </details>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Belum ada komisi tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($recentWithdrawals->isNotEmpty())
    <details class="mt-5 rounded-2xl bg-white border border-slate-200">
        <summary class="px-5 py-3 text-xs font-bold text-slate-600 cursor-pointer">Riwayat pencairan affiliate</summary>
        <div class="px-5 pb-4 space-y-1.5 text-xs">
            @foreach ($recentWithdrawals as $w)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-slate-500">{{ $w->reference }}</span>
                    <span class="font-bold">{{ $w->partner?->user?->name }}</span>
                    <span>{{ $rp($w->amount) }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $w->statusBadgeClass() }}">{{ $w->statusLabel() }}</span>
                    <span class="text-slate-400">{{ format_wib($w->reviewed_at ?? $w->updated_at) }}</span>
                    @if ($w->transfer_reference)<span class="text-slate-500">Ref {{ $w->transfer_reference }}</span>@endif
                </div>
            @endforeach
        </div>
    </details>
@endif
@endsection
