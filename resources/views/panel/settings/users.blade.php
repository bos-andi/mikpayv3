@extends('layouts.panel')

@section('title', 'Manajemen User — MikPay')
@section('page', 'settings-users')

@section('content')
@php
    $me = auth()->user();
    $input = 'mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm px-3 py-2 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500';
    $staffCount = $users->where('role', \App\Models\User::ROLE_TENANT_USER)->count();
    $adminCount = $users->where('role', \App\Models\User::ROLE_TENANT_ADMIN)->count();
    $groups = collect($modules)->groupBy('group', preserveKeys: true);
    $presetIcons = collect($presets)->map(fn ($p) => $p['icon']);
    $userPayload = $users->map(fn ($u) => [
        'id' => $u->id,
        'name' => $u->name,
        'email' => $u->email,
        'role' => $u->role,
        'permissions' => $u->allowedModules(),
        'url' => panel_route('settings.users.update', $u),
        'self' => (int) $u->id === (int) $me->id,
    ])->values();
    $formState = old('_form') ? [
        'form' => old('_form'),
        'name' => old('name'),
        'email' => old('email'),
        'role' => old('role'),
        'permissions' => old('permissions', []),
    ] : null;
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pengaturan Sistem</p>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-user-shield text-indigo-500 text-base"></i>
            Manajemen User
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Tambah akun staf dan atur menu apa saja yang boleh mereka buka.</p>
    </div>
    <button type="button" data-user-create class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-lg shadow-indigo-600/20 transition">
        <i class="fa-solid fa-user-plus"></i> Tambah User
    </button>
</div>

@if (session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs font-semibold text-rose-700 dark:text-rose-300">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center"><i class="fa-solid fa-users"></i></div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total User</p>
            <p class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $users->count() }}</p>
        </div>
    </div>
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center"><i class="fa-solid fa-crown"></i></div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Admin</p>
            <p class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $adminCount }}</p>
        </div>
    </div>
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center"><i class="fa-solid fa-id-badge"></i></div>
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Staf</p>
            <p class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $staffCount }}</p>
        </div>
    </div>
</div>

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Daftar User</h3>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">Semua user login di alamat panel yang sama dengan email & password masing-masing.</p>
    </div>
    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @if ($users->isEmpty())
            <div class="p-10 text-center text-sm text-slate-500">
                <i class="fa-solid fa-users text-3xl text-slate-300 mb-3"></i>
                <p>Belum ada user di ISP ini.</p>
            </div>
        @endif
        @foreach ($users as $u)
            @php
                $isSelf = (int) $u->id === (int) $me->id;
                $isAdmin = $u->isTenantAdmin();
                $allowed = $u->allowedModules();
                $preset = $isAdmin ? null : \App\Support\TenantAccess::presetFor($allowed);
            @endphp
            <div class="p-4 sm:px-5 flex flex-col lg:flex-row lg:items-center gap-3 {{ $u->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-center gap-3 min-w-0 lg:w-80 shrink-0">
                    <div class="w-10 h-10 shrink-0 rounded-xl {{ $isAdmin ? 'bg-gradient-to-br from-indigo-500 to-violet-600' : 'bg-gradient-to-br from-slate-500 to-slate-600' }} text-white flex items-center justify-center text-xs font-extrabold">
                        {{ strtoupper(mb_substr($u->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
                            {{ $u->name }}
                            @if ($isSelf)
                                <span class="ml-1 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 align-middle">ANDA</span>
                            @endif
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $u->email }}</p>
                    </div>
                </div>

                <div class="flex-1 min-w-0 flex flex-wrap items-center gap-1.5">
                    @if ($isAdmin)
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-lg bg-violet-500/10 text-violet-700 dark:text-violet-300 border border-violet-500/20">
                            <i class="fa-solid fa-crown"></i> Admin · akses penuh
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                            <i class="fa-solid {{ $presetIcons[$preset] ?? 'fa-id-badge' }}"></i> {{ $presets[$preset]['label'] ?? 'Staf' }}
                        </span>
                        @foreach ($allowed as $key)
                            <span class="text-[10px] font-semibold px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                {{ $modules[$key]['label'] ?? $key }}
                            </span>
                        @endforeach
                    @endif
                    @unless ($u->is_active)
                        <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-300">Nonaktif</span>
                    @endunless
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" data-user-edit="{{ $u->id }}" class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                        <i class="fa-solid fa-pen mr-1"></i> Edit
                    </button>
                    @unless ($isSelf)
                        <form method="POST" action="{{ panel_route('settings.users.toggle', $u) }}">
                            @csrf
                            <button type="submit" class="text-[11px] font-bold px-3 py-1.5 rounded-lg border {{ $u->is_active ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-500/10' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-500/10' }}">
                                {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ panel_route('settings.users.destroy', $u) }}" onsubmit="return MikPay.confirmForm(event, 'Hapus user {{ e($u->name) }}? Akun ini tidak bisa login lagi.', { title: 'Hapus User', variant: 'danger', confirmText: 'Hapus' })">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] font-bold px-3 py-1.5 rounded-lg border border-rose-300 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">Hapus</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 px-5 py-4 text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
    <p class="font-bold text-slate-700 dark:text-slate-200 mb-1"><i class="fa-solid fa-lock mr-1 text-slate-400"></i> Khusus admin</p>
    Menu <b>Langganan MikPay</b>, <b>Payment Gateway</b>, <b>Manajemen User</b>, serta aksi <b>tarik dana</b> dan <b>pengaturan Toko Voucher</b> hanya bisa dibuka oleh admin. Staf hanya melihat menu yang dicentang; menu lain disembunyikan dan ditolak walau URL-nya dibuka langsung.
</div>

{{-- Modal Tambah / Edit User --}}
<div id="userModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800 sticky top-0 bg-white dark:bg-slate-900 z-10">
            <h3 id="userModalTitle" class="font-bold text-sm text-slate-900 dark:text-white">Tambah User</h3>
            <button type="button" data-user-close class="w-8 h-8 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="userForm" method="POST" action="{{ panel_route('settings.users.store') }}" class="p-5 space-y-5">
            @csrf
            <input type="hidden" name="_method" value="POST" id="userFormMethod">
            <input type="hidden" name="_form" value="create" id="userFormMode">

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 dark:border-rose-500/30 px-4 py-3 text-xs text-rose-700 dark:text-rose-300 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Nama</label>
                    <input type="text" name="name" required maxlength="120" placeholder="Contoh: Budi (Kasir)" class="{{ $input }}">
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Email login</label>
                    <input type="email" name="email" required maxlength="190" placeholder="budi@email.com" class="{{ $input }}">
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Password</label>
                    <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="{{ $input }}">
                    <p id="userPasswordHint" class="hidden text-[10px] text-slate-400 mt-1">Kosongkan jika tidak ingin mengganti password.</p>
                </div>
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Ulangi password</label>
                    <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label class="text-[11px] font-bold text-slate-500 uppercase">Hak akses</label>
                <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 has-[:checked]:border-indigo-400 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500/30">
                        <input type="radio" name="role" value="tenant_user" class="mt-1 accent-indigo-600" checked>
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-id-badge mr-1 text-emerald-500"></i> Staf</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Hanya menu yang dicentang di bawah.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 has-[:checked]:border-indigo-400 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500/30">
                        <input type="radio" name="role" value="tenant_admin" class="mt-1 accent-indigo-600">
                        <span>
                            <span class="block text-xs font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid fa-crown mr-1 text-violet-500"></i> Admin</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Akses penuh, termasuk langganan, tarik dana & manajemen user.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="userAccessSection" class="space-y-4">
                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Template peran</label>
                    <div class="mt-1 grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach ($presets as $key => $preset)
                            <button type="button" data-preset="{{ $key }}" data-modules='@json($preset['modules'])'
                                    class="text-left rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition data-[active=true]:border-indigo-400 data-[active=true]:bg-indigo-50 dark:data-[active=true]:bg-indigo-500/10">
                                <span class="block text-xs font-bold text-slate-800 dark:text-slate-100"><i class="fa-solid {{ $preset['icon'] }} mr-1 text-indigo-500"></i> {{ $preset['label'] }}</span>
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $preset['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Menu yang boleh dibuka</label>
                    <p class="text-[10px] text-slate-400">Dashboard selalu bisa dibuka.</p>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                        @foreach ($groups as $groupName => $items)
                            <div class="space-y-1.5">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $groupName }}</p>
                                @foreach ($items as $key => $module)
                                    <label class="flex items-start gap-2.5 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50/60 dark:has-[:checked]:bg-emerald-500/10">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="mt-0.5 accent-emerald-600" data-module>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-100"><i class="fa-solid {{ $module['icon'] }} w-4 text-slate-400"></i> {{ $module['label'] }}</span>
                                            <span class="block text-[10px] text-slate-500 dark:text-slate-400">{{ $module['desc'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" data-user-close class="text-xs font-bold px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-600/20">
                    <i class="fa-solid fa-floppy-disk"></i> <span id="userSubmitLabel">Simpan User</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var users = @json($userPayload);
    var formState = @json($formState);
    var storeUrl = @json(panel_route('settings.users.store'));
    var modal = document.getElementById('userModal');
    var form = document.getElementById('userForm');
    var accessSection = document.getElementById('userAccessSection');
    var presetButtons = form.querySelectorAll('[data-preset]');
    var moduleBoxes = form.querySelectorAll('[data-module]');
    var roleRadios = form.querySelectorAll('input[name="role"]');

    function selectedModules() {
        return Array.prototype.filter.call(moduleBoxes, function (b) { return b.checked; }).map(function (b) { return b.value; }).sort();
    }

    function syncPreset() {
        var current = JSON.stringify(selectedModules());
        var matched = false;
        presetButtons.forEach(function (btn) {
            var mods = JSON.parse(btn.dataset.modules).sort();
            var isMatch = btn.dataset.preset !== 'custom' && JSON.stringify(mods) === current;
            if (isMatch) matched = true;
            btn.dataset.active = isMatch ? 'true' : 'false';
        });
        if (!matched) {
            form.querySelector('[data-preset="custom"]').dataset.active = 'true';
        }
    }

    function setModules(list) {
        moduleBoxes.forEach(function (b) { b.checked = list.indexOf(b.value) !== -1; });
        syncPreset();
    }

    function syncRole() {
        var role = form.querySelector('input[name="role"]:checked').value;
        var staff = role === 'tenant_user';
        accessSection.classList.toggle('hidden', !staff);
        moduleBoxes.forEach(function (b) { b.disabled = !staff; });
    }

    function open(data) {
        var editing = data.form && data.form.indexOf('edit:') === 0;
        var user = editing ? users.find(function (u) { return 'edit:' + u.id === data.form; }) : null;
        form.action = user ? user.url : storeUrl;
        document.getElementById('userFormMethod').value = user ? 'PUT' : 'POST';
        document.getElementById('userFormMode').value = data.form;
        document.getElementById('userModalTitle').textContent = user ? 'Edit User — ' + user.name : 'Tambah User';
        document.getElementById('userSubmitLabel').textContent = user ? 'Simpan Perubahan' : 'Simpan User';
        document.getElementById('userPasswordHint').classList.toggle('hidden', !user);
        form.password.required = !user;
        form.password_confirmation.required = !user;
        form.password.value = '';
        form.password_confirmation.value = '';
        form.name.value = data.name || '';
        form.email.value = data.email || '';
        roleRadios.forEach(function (r) {
            r.checked = r.value === (data.role || 'tenant_user');
            r.disabled = !!(user && user.self && r.value !== 'tenant_admin');
        });
        setModules(data.permissions || []);
        syncRole();
        modal.classList.remove('hidden');
    }

    function close() { modal.classList.add('hidden'); }

    document.querySelectorAll('[data-user-create]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            open({ form: 'create', role: 'tenant_user', permissions: [] });
        });
    });
    document.querySelectorAll('[data-user-edit]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var u = users.find(function (x) { return String(x.id) === btn.dataset.userEdit; });
            if (u) open({ form: 'edit:' + u.id, name: u.name, email: u.email, role: u.role, permissions: u.role === 'tenant_user' ? u.permissions : [] });
        });
    });
    document.querySelectorAll('[data-user-close]').forEach(function (btn) { btn.addEventListener('click', close); });
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });

    presetButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.preset === 'custom') {
                presetButtons.forEach(function (b) { b.dataset.active = b === btn ? 'true' : 'false'; });
                return;
            }
            setModules(JSON.parse(btn.dataset.modules));
        });
    });
    moduleBoxes.forEach(function (b) { b.addEventListener('change', syncPreset); });
    roleRadios.forEach(function (r) { r.addEventListener('change', syncRole); });

    if (formState) open(formState);
})();
</script>
@endpush
