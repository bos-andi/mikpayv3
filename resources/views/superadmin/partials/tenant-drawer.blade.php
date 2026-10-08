@php
    $panelUrl = 'https://'.$tenant->panelHost();
    $endAt = $tenant->subscriptionEndsAt();
    $subActive = $tenant->subscriptionIsActive();
    $daysLeft = $endAt ? (int) max(0, ceil(wib_now()->diffInSeconds($endAt, false) / 86400)) : null;
    $balance = $tenant->platformBalance();
    $bonus = $tenant->bonusPlatformBalance();
    $owner = $tenant->users->firstWhere('role', 'tenant_admin') ?? $tenant->users->first();
    $phone = (string) $tenant->metaGet('phone', '');
    $initials = collect(preg_split('/\s+/', trim($tenant->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'T';
    $ledger = collect(is_array($tenant->metaGet('platform_balance_ledger')) ? $tenant->metaGet('platform_balance_ledger') : [])->reverse()->take(6);
    $vpn = strtolower((string) ($tenant->vpn_status ?: ''));
    $vpnTone = match ($vpn) {
        'online', 'active', 'connected' => 'emerald',
        'provisioning', 'pending' => 'amber',
        '' => 'slate',
        default => 'rose',
    };
    $statusTone = match ($tenant->status) {
        'active' => 'emerald',
        'trial' => 'amber',
        default => 'rose',
    };
    $toneBadge = [
        'emerald' => 'bg-emerald-400/15 text-emerald-200 border-emerald-300/30',
        'amber' => 'bg-amber-400/15 text-amber-200 border-amber-300/30',
        'rose' => 'bg-rose-400/15 text-rose-200 border-rose-300/30',
        'slate' => 'bg-white/10 text-slate-200 border-white/20',
    ];
    $toneDot = ['emerald' => 'bg-emerald-400', 'amber' => 'bg-amber-400', 'rose' => 'bg-rose-400', 'slate' => 'bg-slate-400'];
    $deletePayload = [
        'id' => $tenant->id,
        'name' => $tenant->name,
        'subdomain' => $tenant->subdomain,
        'fqdn' => $tenant->subdomain.'.'.$tenantBase,
        'url' => route('superadmin.tenants.destroy', $tenant),
    ];
    $isInactive = $tenant->status === 'inactive';
    $toggleConfirm = "return MikPay.confirmForm(event, { title: '".($isInactive ? 'Aktifkan tenant?' : 'Nonaktifkan tenant?')."', message: '".($isInactive ? 'Tenant akan diaktifkan kembali.' : 'Tenant akan dinonaktifkan. Panel terkunci dan mereka bisa bayar sendiri di halaman Langganan untuk aktif kembali.')."', variant: '".($isInactive ? 'default' : 'warning')."', confirmText: '".($isInactive ? 'Ya, aktifkan' : 'Ya, nonaktifkan')."' })";
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition';
    $label = 'block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5';
@endphp

<div id="tenant-modal-{{ $tenant->id }}" class="tenant-drawer fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div data-drawer-backdrop class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm opacity-0 transition-opacity duration-200" onclick="closeTenantDetail({{ $tenant->id }})"></div>

    <div data-drawer-panel class="absolute inset-y-0 right-0 w-full max-w-2xl bg-slate-50 shadow-2xl flex flex-col translate-x-full transition-transform duration-300 ease-out">

        {{-- Hero --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-indigo-800 text-white px-6 pt-5 pb-5 shrink-0">
            <div class="pointer-events-none absolute -top-20 -right-10 w-64 h-64 rounded-full bg-violet-500/25 blur-3xl"></div>
            <div class="relative flex items-start justify-between gap-3">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-300">Detail Tenant · #{{ $tenant->id }}</p>
                <button type="button" onclick="closeTenantDetail({{ $tenant->id }})" class="w-8 h-8 -mt-1 -mr-1 rounded-lg text-indigo-200 hover:text-white hover:bg-white/10 flex items-center justify-center transition" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="relative mt-3 flex items-center gap-4">
                <div class="w-14 h-14 shrink-0 rounded-2xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-lg font-extrabold shadow-lg shadow-indigo-950/40 ring-2 ring-white/20">
                    {{ $initials }}
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-extrabold leading-tight truncate">{{ $tenant->name }}</h3>
                    <a href="{{ $panelUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[11px] font-mono text-indigo-200 hover:text-white transition">
                        {{ $tenant->subdomain }}.{{ $tenantBase }} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[10px] font-bold {{ $toneBadge[$statusTone] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $toneDot[$statusTone] }}"></span> {{ strtoupper($tenant->statusLabel()) }}
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[10px] font-bold bg-white/10 text-white border-white/20">
                            <i class="fa-solid fa-crown text-[9px] text-amber-300"></i> {{ \App\Services\SubscriptionPackageService::planLabel($tenant->plan) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full border text-[10px] font-bold {{ $toneBadge[$vpnTone] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $toneDot[$vpnTone] }}"></span> VPN {{ strtoupper($vpn ?: '—') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="relative mt-5 flex flex-wrap gap-2">
                <a href="{{ $panelUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-white text-indigo-900 text-xs font-extrabold px-3.5 py-2 hover:bg-indigo-50 transition">
                    <i class="fa-solid fa-gauge-high"></i> Buka Panel
                </a>
                <form method="POST" action="{{ route('superadmin.tenants.toggle', $tenant) }}" onsubmit="{{ $toggleConfirm }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-xs font-bold px-3.5 py-2 transition">
                        <i class="fa-solid {{ $isInactive ? 'fa-circle-play text-emerald-300' : 'fa-circle-pause text-amber-300' }}"></i>
                        {{ $isInactive ? 'Aktifkan' : 'Nonaktifkan' }}
                    </button>
                </form>
                @if ($owner && $phone)
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $phone)) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/5 hover:bg-white/10 text-xs font-bold px-3.5 py-2 transition">
                        <i class="fa-brands fa-whatsapp text-emerald-300"></i> WhatsApp
                    </a>
                @endif
            </div>
        </div>

        {{-- Stat strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-slate-200 border-b border-slate-200 shrink-0">
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saldo</p>
                <p class="text-sm font-extrabold text-slate-900 tabular-nums mt-0.5">Rp {{ number_format($balance, 0, ',', '.') }}</p>
                @if ($bonus > 0)<p class="text-[10px] text-amber-600 font-semibold">bonus Rp {{ number_format($bonus, 0, ',', '.') }}</p>@endif
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Masa aktif</p>
                <p class="text-sm font-extrabold tabular-nums mt-0.5 {{ $subActive ? 'text-emerald-600' : 'text-rose-600' }}">{{ $daysLeft !== null ? $daysLeft.' hari' : '—' }}</p>
                <p class="text-[10px] text-slate-400 truncate">{{ $endAt ? $endAt->locale('id')->translatedFormat('d M Y') : 'belum ada' }}</p>
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resource</p>
                <p class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $tenant->routers_count }} <span class="text-[10px] font-semibold text-slate-400">router</span> · {{ $tenant->tunnel_accounts_count }} <span class="text-[10px] font-semibold text-slate-400">VPN</span></p>
                <p class="text-[10px] text-slate-400">{{ $tenant->users_count }} akun login</p>
            </div>
            <div class="bg-white px-4 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Terdaftar</p>
                <p class="text-sm font-extrabold text-slate-900 mt-0.5">{{ optional($tenant->created_at)->locale('id')->translatedFormat('d M Y') ?? '—' }}</p>
                <p class="text-[10px] text-slate-400">{{ optional($tenant->created_at)->locale('id')->diffForHumans() }}</p>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-white border-b border-slate-200 px-4 flex gap-1 shrink-0">
            @foreach (['config' => ['fa-sliders', 'Konfigurasi'], 'saldo' => ['fa-wallet', 'Saldo'], 'users' => ['fa-users', 'Pengguna ('.$tenant->users->count().')']] as $key => [$icon, $text])
                <button type="button" data-tab-btn="{{ $key }}" onclick="switchTenantTab({{ $tenant->id }}, '{{ $key }}')"
                        class="relative inline-flex items-center gap-2 px-3 py-3 text-xs font-bold text-slate-500 hover:text-slate-900 transition">
                    <i class="fa-solid {{ $icon }} text-[11px]"></i> {{ $text }}
                    <span data-tab-line class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-indigo-600 hidden"></span>
                </button>
            @endforeach
        </div>

        <div class="flex-1 overflow-y-auto">
            {{-- Konfigurasi --}}
            <div data-tab-pane="config" class="p-5 sm:p-6 space-y-5">
                <div class="rounded-2xl bg-white border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-xs">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pemilik</p>
                        <p class="font-bold text-slate-900 mt-0.5 truncate">{{ $owner?->name ?? '—' }}</p>
                        <p class="text-[11px] font-mono text-slate-500 truncate">{{ $owner?->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">WhatsApp</p>
                        <p class="font-bold text-slate-900 mt-0.5">{{ $phone !== '' ? $phone : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Remote host</p>
                        <p class="font-mono text-[11px] text-slate-700 mt-0.5 truncate">{{ $tenant->remote_host ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Langganan berakhir</p>
                        <p class="font-bold mt-0.5 {{ $subActive ? 'text-slate-900' : 'text-rose-600' }}">{{ $endAt ? format_wib($endAt) : '—' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('superadmin.tenants.update', $tenant) }}" class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                    @csrf
                    @method('PUT')
                    @if ($q !== '')
                        <input type="hidden" name="q" value="{{ $q }}">
                    @endif
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-indigo-500 text-xs"></i>
                        <p class="text-xs font-extrabold text-slate-900">Konfigurasi Tenant</p>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="{{ $label }}">Nama tenant</label>
                            <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Subdomain</label>
                            <div class="flex items-stretch rounded-xl border border-slate-200 bg-white focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 overflow-hidden transition">
                                <input type="text" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" required
                                       pattern="[a-z0-9]([a-z0-9-]{1,28}[a-z0-9])?"
                                       class="flex-1 min-w-0 px-3 py-2.5 text-xs font-mono lowercase focus:outline-none">
                                <span class="px-3 flex items-center bg-slate-50 border-l border-slate-200 text-[11px] font-mono text-slate-500">.{{ $tenantBase }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5"><i class="fa-solid fa-circle-info mr-0.5"></i> Mengubah subdomain juga memperbarui DNS Hostinger.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $label }}">Status</label>
                                <select name="status" class="{{ $input }}">
                                    @foreach ($statusOptions as $val => $text)
                                        <option value="{{ $val }}" @selected(old('status', $tenant->status) === $val)>{{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Paket</label>
                                <select name="plan" class="{{ $input }}">
                                    @foreach ($planOptions as $val => $text)
                                        <option value="{{ $val }}" @selected(old('plan', $tenant->plan) === $val)>{{ $text }}</option>
                                    @endforeach
                                    @if ($tenant->plan && ! isset($planOptions[$tenant->plan]))
                                        <option value="{{ $tenant->plan }}" selected>{{ \App\Services\SubscriptionPackageService::planLabel($tenant->plan) }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2.5 transition">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>

                <div class="rounded-2xl border border-rose-200 bg-white overflow-hidden">
                    <div class="px-4 py-3 border-b border-rose-100 bg-rose-50/60 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>
                        <p class="text-xs font-extrabold text-rose-800">Zona Berbahaya</p>
                    </div>
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-slate-900">Hapus tenant permanen</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Akun login, OpenVPN, data pelanggan, firewall &amp; DNS subdomain ikut dihapus. Tidak bisa dibatalkan.</p>
                        </div>
                        <button type="button" data-delete-tenant="{{ e(json_encode($deletePayload)) }}" onclick="destroyTenantWithProgress(this)"
                                class="shrink-0 inline-flex items-center gap-2 rounded-xl border border-rose-300 text-rose-600 hover:bg-rose-600 hover:text-white hover:border-rose-600 text-xs font-bold px-4 py-2.5 transition">
                            <i class="fa-solid fa-trash-can"></i> Hapus Tenant
                        </button>
                    </div>
                </div>
            </div>

            {{-- Saldo --}}
            <div data-tab-pane="saldo" class="p-5 sm:p-6 space-y-5 hidden">
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white p-5 shadow-lg shadow-emerald-700/20">
                    <div class="pointer-events-none absolute -bottom-16 -right-10 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
                    <p class="relative text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-100">Saldo MikPay tenant</p>
                    <p class="relative text-3xl font-extrabold tabular-nums mt-1">Rp {{ number_format($balance, 0, ',', '.') }}</p>
                    <p class="relative text-[11px] text-emerald-100 mt-1">
                        @if ($bonus > 0)
                            Termasuk saldo bonus Rp {{ number_format($bonus, 0, ',', '.') }} (tidak menghasilkan komisi affiliate)
                        @else
                            Dipakai tenant untuk perpanjang langganan.
                        @endif
                    </p>
                </div>

                <form id="inject-saldo-{{ $tenant->id }}" method="POST" action="{{ route('superadmin.tenants.inject-saldo', $tenant) }}"
                      class="rounded-2xl bg-white border border-slate-200 overflow-hidden"
                      onsubmit="return MikPay.confirmForm(event, { title: 'Inject saldo tenant?', message: 'Tambahkan saldo ke {{ addslashes($tenant->name) }}? Tenant bisa memakainya untuk perpanjang langganan.', variant: 'default', confirmText: 'Ya, inject saldo' })">
                    @csrf
                    @if ($q !== '')
                        <input type="hidden" name="q" value="{{ $q }}">
                    @endif
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-coins text-emerald-500 text-xs"></i>
                            <p class="text-xs font-extrabold text-slate-900">Inject Saldo Bonus</p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">Manual · superadmin</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="{{ $label }}">Nominal</label>
                            <div class="flex items-center rounded-xl border border-slate-200 bg-white focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10 transition">
                                <span class="pl-3 pr-1.5 text-xs font-extrabold text-slate-400">Rp</span>
                                <input type="number" name="amount" min="1000" step="1000" required placeholder="50000"
                                       class="w-full bg-transparent py-2.5 pr-3 text-sm font-extrabold text-slate-900 tabular-nums focus:outline-none">
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach ([35000, 50000, 80000, 100000, 155000] as $preset)
                                    <button type="button" onclick="this.closest('form').querySelector('[name=amount]').value = {{ $preset }}"
                                            class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:border-emerald-400 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                        Rp {{ number_format($preset, 0, ',', '.') }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Catatan <span class="normal-case font-semibold text-slate-400">(opsional)</span></label>
                            <input type="text" name="note" maxlength="500" placeholder="Contoh: Kompensasi gangguan / bonus onboarding" class="{{ $input }}">
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                        <p class="text-[10px] text-slate-400">Pembayaran tenant tetap otomatis via Xendit.</p>
                        <button type="submit" class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2.5 transition">
                            <i class="fa-solid fa-plus"></i> Inject Saldo
                        </button>
                    </div>
                </form>

                <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <p class="text-xs font-extrabold text-slate-900">Mutasi saldo terakhir</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($ledger as $row)
                            @php $credit = ($row['type'] ?? '') === 'credit'; @endphp
                            <div class="px-4 py-2.5 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center {{ $credit ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600' }}">
                                        <i class="fa-solid {{ $credit ? 'fa-arrow-down' : 'fa-arrow-up' }} text-[10px]"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-700 truncate">{{ ($row['note'] ?? '') !== '' ? $row['note'] : ($credit ? 'Saldo masuk' : 'Saldo terpakai') }}</p>
                                        <p class="text-[10px] text-slate-400">{{ ! empty($row['at']) ? format_wib(\Carbon\Carbon::parse($row['at'])) : '' }}</p>
                                    </div>
                                </div>
                                <p class="shrink-0 font-extrabold tabular-nums {{ $credit ? 'text-emerald-600' : 'text-slate-700' }}">{{ $credit ? '+' : '−' }}Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-center text-[11px] text-slate-400">Belum ada mutasi saldo.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Pengguna --}}
            <div data-tab-pane="users" class="p-5 sm:p-6 space-y-4 hidden">
                @forelse ($tenant->users as $user)
                    @php
                        $uInitials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';
                    @endphp
                    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">
                        <div class="p-4 flex items-start gap-3">
                            <div class="w-10 h-10 shrink-0 rounded-xl {{ $user->role === 'tenant_admin' ? 'bg-indigo-600' : 'bg-slate-500' }} text-white flex items-center justify-center text-xs font-extrabold">{{ $uInitials }}</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-extrabold text-slate-900 truncate">{{ $user->name }}</p>
                                <p class="text-[11px] font-mono text-slate-500 truncate">{{ $user->email }}</p>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-indigo-50 text-indigo-700">{{ $roleLabel[$user->role] ?? $user->role }}</span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md {{ $user->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $user->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                                    @if ($user->hasVerifiedEmail())
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600"><i class="fa-solid fa-envelope-circle-check"></i> EMAIL OK</span>
                                    @else
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-700">EMAIL BELUM VERIFIKASI</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @unless ($user->hasVerifiedEmail())
                                    <form method="POST" action="{{ route('superadmin.users.verify-email', $user) }}"
                                          onsubmit="return MikPay.confirmForm(event, { title: 'Verifikasi email manual?', message: 'Tandai {{ addslashes($user->email) }} sebagai terverifikasi dan aktifkan panel/trial-nya. Lakukan hanya jika Anda sudah memastikan email ini benar.' })">
                                        @csrf
                                        <button type="submit" title="Verifikasi email manual" class="w-8 h-8 rounded-lg border border-amber-200 text-amber-600 hover:bg-amber-50 flex items-center justify-center transition"><i class="fa-solid fa-envelope-circle-check text-xs"></i></button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('superadmin.users.toggle', $user) }}"
                                      onsubmit="return MikPay.confirmForm(event, { title: '{{ $user->is_active ? 'Nonaktifkan user?' : 'Aktifkan user?' }}', message: '{{ $user->is_active ? 'User tidak bisa login sampai diaktifkan lagi.' : 'User bisa login kembali.' }}', variant: '{{ $user->is_active ? 'warning' : 'default' }}' })">
                                    @csrf
                                    <button type="submit" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 flex items-center justify-center transition"><i class="fa-solid {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }} text-xs"></i></button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}"
                                      onsubmit="return MikPay.confirmForm(event, { title: 'Hapus user?', message: 'Hapus user {{ addslashes($user->email) }}? Akun login ini akan dihapus permanen.', variant: 'danger', confirmText: 'Ya, hapus' })">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus user" class="w-8 h-8 rounded-lg border border-rose-200 text-rose-500 hover:bg-rose-50 flex items-center justify-center transition"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                </form>
                            </div>
                        </div>
                        <details class="group border-t border-slate-100">
                            <summary class="cursor-pointer list-none px-4 py-2.5 text-[11px] font-bold text-indigo-600 hover:bg-slate-50 flex items-center justify-between">
                                <span><i class="fa-solid fa-pen-to-square mr-1"></i> Edit user</span>
                                <i class="fa-solid fa-chevron-down text-[9px] transition group-open:rotate-180"></i>
                            </summary>
                            <form method="POST" action="{{ route('superadmin.users.update', $user) }}" class="px-4 pb-4 pt-1 space-y-3">
                                @csrf
                                @method('PUT')
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $label }}">Nama</label>
                                        <input type="text" name="name" value="{{ $user->name }}" required class="{{ $input }}">
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">Role</label>
                                        <select name="role" class="{{ $input }}">
                                            <option value="tenant_admin" @selected($user->role === 'tenant_admin')>Admin Tenant</option>
                                            <option value="tenant_user" @selected($user->role === 'tenant_user')>User Tenant</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="{{ $label }}">Email</label>
                                    <input type="email" name="email" value="{{ $user->email }}" required class="{{ $input }}">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="{{ $label }}">Password baru</label>
                                        <input type="password" name="password" class="{{ $input }}" placeholder="Kosongkan jika tetap" autocomplete="new-password">
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">Ulangi password</label>
                                        <input type="password" name="password_confirmation" class="{{ $input }}" autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <label class="inline-flex items-center gap-2 text-[11px] font-semibold text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="rounded border-slate-300 text-indigo-600">
                                        Akun aktif
                                    </label>
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2 transition">
                                        <i class="fa-solid fa-floppy-disk"></i> Simpan User
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
                        <i class="fa-solid fa-user-plus text-2xl text-slate-300"></i>
                        <p class="text-xs text-slate-500 mt-2">Belum ada user di tenant ini.</p>
                    </div>
                @endforelse

                <details class="group rounded-2xl bg-white border border-slate-200 overflow-hidden" {{ $tenant->users->isEmpty() ? 'open' : '' }}>
                    <summary class="cursor-pointer list-none px-4 py-3 flex items-center justify-between hover:bg-slate-50">
                        <span class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-900">
                            <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center"><i class="fa-solid fa-plus text-[10px]"></i></span>
                            Tambah user ke tenant
                        </span>
                        <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 transition group-open:rotate-180"></i>
                    </summary>
                    <form method="POST" action="{{ route('superadmin.tenants.users.store', $tenant) }}" class="px-4 pb-4 pt-1 space-y-3 border-t border-slate-100">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 pt-3">
                            <div>
                                <label class="{{ $label }}">Nama</label>
                                <input type="text" name="name" required class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Role</label>
                                <select name="role" class="{{ $input }}">
                                    <option value="tenant_admin">Admin Tenant</option>
                                    <option value="tenant_user">User Tenant</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Email</label>
                            <input type="email" name="email" required class="{{ $input }}">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $label }}">Password</label>
                                <input type="password" name="password" required class="{{ $input }}" autocomplete="new-password">
                            </div>
                            <div>
                                <label class="{{ $label }}">Ulangi</label>
                                <input type="password" name="password_confirmation" required class="{{ $input }}" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold px-4 py-2 transition">
                                <i class="fa-solid fa-user-plus"></i> Buat User
                            </button>
                        </div>
                    </form>
                </details>
            </div>
        </div>
    </div>
</div>
