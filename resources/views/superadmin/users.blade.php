@extends('layouts.superadmin')

@section('title', 'Pengguna & Tenant — Superadmin')
@section('heading', 'Pengguna & Tenant')

@section('content')
@php
    $planOptions = ['trial' => 'Trial'];
    foreach (\App\Services\SubscriptionPackageService::all() as $planKey => $planPkg) {
        $planOptions[$planKey] = 'MikPay '.$planPkg['name'];
    }
    $statusOptions = ['active' => 'Aktif', 'trial' => 'Trial', 'inactive' => 'Nonaktif'];
    $roleLabel = [
        'superadmin' => 'Superadmin',
        'tenant_admin' => 'Admin Tenant',
        'tenant_user' => 'User Tenant',
    ];
    $tenantBase = $tenantBase ?? config('mikpay.tenant_base_domain', 'mikpay.link');
    $openTenantId = (int) ($openTenantId ?? 0);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Tenant</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $tenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tenant Aktif</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $activeTenantCount }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">User (non-superadmin)</p>
        <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $userCount }}</p>
    </div>
</div>

<form method="GET" action="{{ route('superadmin.users') }}" class="mb-6 flex flex-col sm:flex-row gap-2">
    <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari tenant, subdomain, nama, atau email..."
               class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs focus:outline-none focus:border-indigo-500">
    </div>
    <button type="submit" class="text-xs font-bold px-4 py-2.5 rounded-xl bg-slate-900 text-white">Cari</button>
    @if ($q !== '')
        <a href="{{ route('superadmin.users') }}" class="text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 text-center">Reset</a>
    @endif
</form>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 font-bold">Tenant</th>
                    <th class="px-4 py-3 font-bold">Subdomain</th>
                    <th class="px-4 py-3 font-bold">Paket</th>
                    <th class="px-4 py-3 font-bold">Status</th>
                    <th class="px-4 py-3 font-bold">Resource</th>
                    <th class="px-4 py-3 font-bold">Saldo</th>
                    <th class="px-4 py-3 font-bold">Trial / Langganan</th>
                    <th class="px-4 py-3 font-bold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($tenants as $tenant)
                    @php
                        $panelUrl = 'https://'.$tenant->panelHost();
                        $statusClass = match ($tenant->status) {
                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'inactive' => 'bg-rose-50 text-rose-700 border-rose-200',
                            'trial' => 'bg-amber-50 text-amber-700 border-amber-200',
                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                        };
                        $endAt = $tenant->subscriptionEndsAt();
                        $isActiveSub = $tenant->subscriptionIsActive();
                    @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3">
                            <p class="font-extrabold text-slate-900">{{ $tenant->name }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">ID #{{ $tenant->id }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ $panelUrl }}" target="_blank" rel="noopener" class="font-mono text-[11px] text-indigo-600 hover:underline">
                                {{ $tenant->subdomain }}.{{ $tenantBase }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ \App\Services\SubscriptionPackageService::planLabel($tenant->plan) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $statusClass }}">
                                {{ strtoupper($tenant->statusLabel()) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                            {{ $tenant->users_count }} user · {{ $tenant->routers_count }} router · {{ $tenant->tunnel_accounts_count }} tunnel
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <p class="font-bold text-emerald-700">Rp {{ number_format($tenant->platformBalance(), 0, ',', '.') }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($endAt)
                                <span class="{{ $isActiveSub ? 'text-emerald-700' : 'text-rose-600' }} font-semibold">
                                    {{ format_wib($endAt) }}
                                </span>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $isActiveSub ? 'Aktif' : 'Habis' }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center justify-end gap-1.5">
                                <button type="button"
                                        onclick="openTenantDetail({{ $tenant->id }})"
                                        class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                                    Detail
                                </button>
                                <button type="button"
                                        onclick="openTenantDetail({{ $tenant->id }}, 'saldo')"
                                        class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                    Inject Saldo
                                </button>
                                <form method="POST" action="{{ route('superadmin.tenants.toggle', $tenant) }}"
                                      onsubmit="return MikPay.confirmForm(event, { title: '{{ $tenant->status === 'inactive' ? 'Aktifkan tenant?' : 'Nonaktifkan tenant?' }}', message: '{{ $tenant->status === 'inactive' ? 'Tenant akan diaktifkan kembali.' : 'Tenant akan dinonaktifkan. Panel terkunci dan mereka bisa bayar sendiri di halaman Langganan untuk aktif kembali.' }}', variant: '{{ $tenant->status === 'inactive' ? 'default' : 'warning' }}', confirmText: '{{ $tenant->status === 'inactive' ? 'Ya, aktifkan' : 'Ya, nonaktifkan' }}' })">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border {{ $tenant->status === 'inactive' ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' : 'border-amber-300 text-amber-700 hover:bg-amber-50' }}">
                                        {{ $tenant->status === 'inactive' ? 'Aktifkan' : 'Nonaktifkan' }}
                                    </button>
                                </form>
                                @php
                                    $deletePayload = [
                                        'id' => $tenant->id,
                                        'name' => $tenant->name,
                                        'subdomain' => $tenant->subdomain,
                                        'fqdn' => $tenant->subdomain.'.'.$tenantBase,
                                        'url' => route('superadmin.tenants.destroy', $tenant),
                                    ];
                                @endphp
                                <button type="button"
                                        data-delete-tenant="{{ e(json_encode($deletePayload)) }}"
                                        onclick="destroyTenantWithProgress(this)"
                                        class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg border border-rose-300 text-rose-600 hover:bg-rose-50">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                            Tidak ada tenant{{ $q !== '' ? ' untuk pencarian ini' : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Detail drawers --}}
@foreach ($tenants as $tenant)
    @include('superadmin.partials.tenant-drawer')
@endforeach

@if ($superadmins->isNotEmpty())
    <div class="mt-6 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Superadmin</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Akun platform (bukan milik tenant).</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($superadmins as $admin)
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-900">{{ $admin->name }}</p>
                        <p class="text-[11px] font-mono text-slate-500">{{ $admin->email }}</p>
                    </div>
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $admin->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $admin->is_active ? 'AKTIF' : 'NONAKTIF' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($orphanUsers->isNotEmpty())
    <div class="mt-6 bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <h3 class="text-sm font-bold text-amber-900">User tanpa tenant</h3>
        <ul class="mt-2 space-y-1 text-xs text-amber-800">
            @foreach ($orphanUsers as $u)
                <li>{{ $u->name }} · {{ $u->email }} · {{ $u->role }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Progress penghapusan tenant --}}
<div id="tenantDeleteProgress" class="fixed inset-0 z-[9500] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 pt-5 pb-3 border-b border-slate-100 bg-gradient-to-br from-rose-50 to-violet-50">
            <div class="flex items-center gap-3">
                <div id="tenantDeleteIcon" class="h-11 w-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-trash-can text-lg"></i>
                </div>
                <div class="min-w-0">
                    <p id="tenantDeleteTitle" class="text-sm font-extrabold text-slate-900">Menghapus tenant…</p>
                    <p id="tenantDeleteSubtitle" class="text-[11px] text-slate-500 mt-0.5 font-mono truncate">—</p>
                </div>
            </div>
            <div class="mt-4 h-2 rounded-full bg-slate-200 overflow-hidden">
                <div id="tenantDeleteBar" class="h-full w-0 rounded-full bg-gradient-to-r from-violet-600 to-rose-500 transition-all duration-500 ease-out"></div>
            </div>
            <p id="tenantDeletePct" class="text-[10px] font-bold text-slate-500 mt-1.5">0%</p>
        </div>
        <div id="tenantDeleteSteps" class="px-5 py-4 space-y-2.5 max-h-[50vh] overflow-y-auto"></div>
        <div id="tenantDeleteFooter" class="hidden px-5 pb-5">
            <button type="button" id="tenantDeleteDoneBtn" class="w-full text-xs font-bold px-3 py-2.5 rounded-2xl bg-slate-900 text-white">
                Selesai
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function switchTenantTab(id, tab) {
    const modal = document.getElementById('tenant-modal-' + id);
    if (!modal) return;
    modal.querySelectorAll('[data-tab-pane]').forEach((p) => p.classList.toggle('hidden', p.dataset.tabPane !== tab));
    modal.querySelectorAll('[data-tab-btn]').forEach((b) => {
        const on = b.dataset.tabBtn === tab;
        b.classList.toggle('text-indigo-700', on);
        b.classList.toggle('text-slate-500', !on);
        b.querySelector('[data-tab-line]')?.classList.toggle('hidden', !on);
    });
}
function hideTenantDrawer(modal, instant = false) {
    if (!modal || modal.classList.contains('hidden')) return;
    modal.querySelector('[data-drawer-panel]')?.classList.add('translate-x-full');
    modal.querySelector('[data-drawer-backdrop]')?.classList.add('opacity-0');
    if (instant) {
        modal.classList.add('hidden');
    } else {
        setTimeout(() => modal.classList.add('hidden'), 250);
    }
}
function openTenantDetail(id, tab = 'config') {
    document.querySelectorAll('.tenant-drawer').forEach((el) => hideTenantDrawer(el, true));
    const modal = document.getElementById('tenant-modal-' + id);
    if (!modal) return;
    switchTenantTab(id, tab);
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    const panel = modal.querySelector('[data-drawer-panel]');
    void panel?.offsetWidth;
    panel?.classList.remove('translate-x-full');
    modal.querySelector('[data-drawer-backdrop]')?.classList.remove('opacity-0');
}
function closeTenantDetail(id) {
    hideTenantDrawer(document.getElementById('tenant-modal-' + id));
    document.body.classList.remove('overflow-hidden');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const progress = document.getElementById('tenantDeleteProgress');
        if (progress && !progress.classList.contains('hidden') && progress.dataset.locked === '1') {
            return;
        }
        document.querySelectorAll('.tenant-drawer').forEach((el) => hideTenantDrawer(el));
        document.body.classList.remove('overflow-hidden');
    }
});

const pendingSteps = [
    { key: 'users', label: 'Hapus akun login panel' },
    { key: 'tunnels', label: 'Hapus OpenVPN & port remote' },
    { key: 'data', label: 'Hapus data tenant (router, billing, GIS, keuangan)' },
    { key: 'sessions', label: 'Putus sesi VPN yang masih tersambung' },
    { key: 'firewall', label: 'Bersihkan firewall tunnel server' },
    { key: 'dns', label: 'Hapus DNS subdomain Hostinger' },
    { key: 'done', label: 'Tenant dihapus permanen' },
];

function renderDeleteSteps(stateMap) {
    const wrap = document.getElementById('tenantDeleteSteps');
    if (!wrap) return;
    wrap.innerHTML = pendingSteps.map((step) => {
        const st = stateMap[step.key] || { status: 'pending', detail: '' };
        let icon = '<span class="w-5 h-5 rounded-full border-2 border-slate-300 inline-flex"></span>';
        let text = 'text-slate-500';
        if (st.status === 'running') {
            icon = '<i class="fa-solid fa-spinner fa-spin text-violet-600 text-sm w-5 text-center"></i>';
            text = 'text-slate-900 font-semibold';
        } else if (st.status === 'ok') {
            icon = '<i class="fa-solid fa-circle-check text-emerald-500 text-sm w-5 text-center"></i>';
            text = 'text-slate-800 font-semibold';
        } else if (st.status === 'fail') {
            icon = '<i class="fa-solid fa-circle-xmark text-rose-500 text-sm w-5 text-center"></i>';
            text = 'text-rose-700 font-semibold';
        }
        return `<div class="flex items-start gap-3">
            <div class="pt-0.5">${icon}</div>
            <div class="min-w-0">
                <p class="text-[12px] ${text}">${step.label}</p>
                ${st.detail ? `<p class="text-[10px] text-slate-400 mt-0.5">${st.detail}</p>` : ''}
            </div>
        </div>`;
    }).join('');
}

function setDeleteProgress(pct) {
    const bar = document.getElementById('tenantDeleteBar');
    const label = document.getElementById('tenantDeletePct');
    if (bar) bar.style.width = Math.max(0, Math.min(100, pct)) + '%';
    if (label) label.textContent = Math.round(pct) + '%';
}

function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

function parseDeletePayload(el) {
    if (!el) return null;
    if (typeof el === 'object' && el.url) return el;
    const raw = el.getAttribute?.('data-delete-tenant') || el.dataset?.deleteTenant || '';
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        console.error('Delete payload parse error', e, raw);
        return null;
    }
}

async function destroyTenantWithProgress(source) {
    const payload = parseDeletePayload(source);
    if (!payload?.url) {
        await MikPay.alert({
            title: 'Tombol delete error',
            message: 'Data tenant tidak valid. Muat ulang halaman lalu coba lagi.',
            variant: 'danger',
        });
        return;
    }

    const ok = await MikPay.confirm({
        title: 'Hapus tenant?',
        message: `HAPUS permanen tenant ${payload.name} (${payload.fqdn}) beserta semua konfigurasi subdomain, user, tunnel, dan data terkait? Tindakan ini tidak bisa dibatalkan.`,
        variant: 'danger',
        confirmText: 'Ya, hapus semua',
    });
    if (!ok) return;

    closeTenantDetail(payload.id);

    const modal = document.getElementById('tenantDeleteProgress');
    const title = document.getElementById('tenantDeleteTitle');
    const subtitle = document.getElementById('tenantDeleteSubtitle');
    const footer = document.getElementById('tenantDeleteFooter');
    const icon = document.getElementById('tenantDeleteIcon');
    const doneBtn = document.getElementById('tenantDeleteDoneBtn');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.dataset.locked = '1';
    document.body.classList.add('overflow-hidden');
    footer.classList.add('hidden');
    title.textContent = 'Menghapus konfigurasi…';
    subtitle.textContent = payload.fqdn || payload.name;
    icon.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-lg"></i>';
    icon.className = 'h-11 w-11 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center shrink-0';

    const stateMap = {};
    pendingSteps.forEach((s) => { stateMap[s.key] = { status: 'pending', detail: '' }; });
    renderDeleteSteps(stateMap);
    setDeleteProgress(8);

    // Animasi langkah berjalan sambil request diproses.
    let i = 0;
    const ticker = setInterval(() => {
        if (i >= pendingSteps.length) return;
        const key = pendingSteps[i].key;
        if (stateMap[key].status === 'pending') {
            stateMap[key] = { status: 'running', detail: 'Memproses…' };
            renderDeleteSteps(stateMap);
            setDeleteProgress(12 + (i / pendingSteps.length) * 55);
        }
        i += 1;
    }, 420);

    try {
        const res = await fetch(payload.url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await res.json().catch(() => ({}));
        clearInterval(ticker);
        await sleep(250);

        const serverSteps = Array.isArray(data.steps) ? data.steps : [];
        for (let idx = 0; idx < pendingSteps.length; idx++) {
            const key = pendingSteps[idx].key;
            const found = serverSteps.find((s) => s.key === key);
            stateMap[key] = {
                status: found ? (found.ok ? 'ok' : 'fail') : (data.ok ? 'ok' : 'fail'),
                detail: found?.detail || '',
            };
            renderDeleteSteps(stateMap);
            setDeleteProgress(70 + ((idx + 1) / pendingSteps.length) * 30);
            await sleep(180);
        }

        if (data.ok) {
            title.textContent = 'Penghapusan selesai';
            subtitle.textContent = data.message || `${payload.name} sudah dihapus`;
            icon.innerHTML = '<i class="fa-solid fa-circle-check text-lg"></i>';
            icon.className = 'h-11 w-11 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0';
            setDeleteProgress(100);
            modal.dataset.locked = '0';
            footer.classList.remove('hidden');
            doneBtn.onclick = () => { window.location.href = data.redirect || @json(route('superadmin.users')); };
            setTimeout(() => { window.location.href = data.redirect || @json(route('superadmin.users')); }, 1600);
        } else {
            title.textContent = 'Penghapusan gagal';
            subtitle.textContent = data.message || 'Terjadi kesalahan';
            icon.innerHTML = '<i class="fa-solid fa-circle-xmark text-lg"></i>';
            icon.className = 'h-11 w-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0';
            modal.dataset.locked = '0';
            footer.classList.remove('hidden');
            doneBtn.textContent = 'Tutup';
            doneBtn.onclick = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };
        }
    } catch (err) {
        clearInterval(ticker);
        title.textContent = 'Penghapusan gagal';
        subtitle.textContent = err?.message || 'Tidak bisa menghubungi server';
        icon.innerHTML = '<i class="fa-solid fa-circle-xmark text-lg"></i>';
        icon.className = 'h-11 w-11 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0';
        modal.dataset.locked = '0';
        footer.classList.remove('hidden');
        doneBtn.textContent = 'Tutup';
        doneBtn.onclick = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };
    }
}

@if ($openTenantId > 0)
openTenantDetail({{ $openTenantId }});
@endif
</script>
@endpush
