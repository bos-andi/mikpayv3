<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>List VPN — MikPay Tunnel</title>
    @include('partials.head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 font-sans antialiased" x-data="tunnelDash(@js($vpnRows))" x-cloak>
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset(config('mikpay.brand.logo', 'logo-mikpay.png')) }}?v=2" alt="MikPay" class="h-9 w-9 object-contain">
                <div>
                    <p class="text-sm font-extrabold">MikPay Tunnel</p>
                    <p class="text-[10px] text-slate-500">{{ auth()->user()?->email ?? 'Guest' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="text-xs font-bold text-violet-600 hover:underline">Landing</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-bold text-slate-500 hover:text-rose-600">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        @if (!empty($success))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ $success }}
            </div>
        @endif

        {{-- LIST --}}
        <div x-show="tab === 'list'" class="space-y-6">
            <div>
                <h1 class="text-xl font-black text-slate-900">List VPN</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Username berbentuk <span class="font-mono font-semibold">nama@tunnel.mikpay.link</span>,
                    akses remote via node <span class="font-mono font-semibold">an-1.mikpay.link:PORT</span>.
                </p>
            </div>

            <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex items-start gap-3.5 text-xs text-indigo-950">
                <div class="p-2 bg-indigo-600 text-white rounded-xl shrink-0">
                    <i class="fa-solid fa-circle-info text-sm"></i>
                </div>
                <div class="space-y-1">
                    <p class="font-bold">Akses Winbox / WebFig / API</p>
                    <p class="text-indigo-900/80 leading-relaxed">
                        Gunakan <span class="font-semibold bg-indigo-100 px-1.5 py-0.5 rounded">Manage</span>
                        untuk script OpenVPN MikroTik (ROS 6 &amp; 7). Port unik per pelanggan membedakan target di node yang sama.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Remote</p>
                    <p class="text-2xl font-black text-slate-900 mt-1 font-mono" x-text="rows.length">0</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Aktif</p>
                    <p class="text-2xl font-black text-emerald-600 mt-1 font-mono" x-text="rows.filter(r => r.status === 'active').length">0</p>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Node Utama</p>
                    <p class="text-lg font-black text-slate-900 mt-1 font-mono truncate">{{ $defaultNodeHost }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse min-w-[720px]">
                        <thead>
                            <tr class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100 text-[11px]">
                                <th class="px-5 py-3.5">Username VPN</th>
                                <th class="px-5 py-3.5">Password</th>
                                <th class="px-5 py-3.5">URL Remote</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-center">Kelola</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="item in rows" :key="item.id">
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900 text-sm font-mono" x-text="item.username"></div>
                                        <div class="text-[11px] text-slate-400 mt-0.5" x-text="item.sourceLabel"></div>
                                    </td>
                                    <td class="px-5 py-4 font-mono">
                                        <div class="flex items-center gap-2">
                                            <span x-text="item.showPass ? item.password : '••••••••'"></span>
                                            <button type="button" @click="item.showPass = !item.showPass" class="text-slate-400 hover:text-slate-700">
                                                <i class="fa-solid" :class="item.showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                                            </button>
                                            <button type="button" @click="copy(item.password, 'Password disalin')" class="text-slate-400 hover:text-violet-600">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-xl font-mono text-[11px] border border-slate-200">
                                            <span class="text-slate-800 font-semibold" x-text="item.remoteUrl"></span>
                                            <i class="fa-solid fa-right-left text-[10px] text-violet-500"></i>
                                            <span class="text-emerald-600 font-bold" x-text="':' + item.targetPort"></span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1" x-text="item.nodeHost + ' · OVPN :' + item.ovpnPort"></p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold"
                                              :class="item.status === 'active'
                                                ? 'bg-emerald-50 text-emerald-600 border border-emerald-200'
                                                : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="item.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                                            <span class="uppercase" x-text="item.status"></span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <button type="button" @click="openManage(item)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-50 hover:bg-violet-100 text-violet-700 font-semibold rounded-xl transition">
                                            <i class="fa-solid fa-sliders text-xs"></i>
                                            <span>Manage</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!rows.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">
                                    Belum ada akun tunnel. Aktifkan langganan MikPay atau beli paket Tunnel.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- MANAGE --}}
        <div x-show="tab === 'detail' && selected" class="space-y-6">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <button type="button" @click="tab = 'list'; selected = null"
                        class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-violet-600 bg-white px-3.5 py-2 rounded-xl border border-slate-200">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke List VPN</span>
                </button>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200"
                      x-show="selected?.status === 'active'">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Tunnel Active
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-show="selected">
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-gradient-to-br from-slate-900 via-violet-950 to-slate-900 rounded-2xl p-6 text-white border border-slate-800">
                        <span class="inline-block px-2.5 py-1 bg-violet-500/20 text-violet-200 text-[11px] font-bold rounded-lg border border-violet-500/30 mb-2 uppercase tracking-wider">
                            OpenVPN TCP
                        </span>
                        <h2 class="text-xl font-black font-mono" x-text="selected?.username"></h2>
                        <p class="text-slate-400 text-xs mt-1">
                            Connect ke <span class="font-mono text-violet-200" x-text="selected?.nodeHost + ':' + selected?.ovpnPort"></span>
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm">Script Terminal MikroTik</h3>
                                <p class="text-xs text-slate-500">Paste di Winbox Terminal (mode=ip, TCP).</p>
                            </div>
                            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                                <button type="button" @click="ros = 'ros7'"
                                        :class="ros === 'ros7' ? 'bg-white text-violet-700 font-bold shadow-sm' : 'text-slate-500'"
                                        class="px-3 py-1 rounded-lg text-xs">RouterOS v7</button>
                                <button type="button" @click="ros = 'ros6'"
                                        :class="ros === 'ros6' ? 'bg-white text-violet-700 font-bold shadow-sm' : 'text-slate-500'"
                                        class="px-3 py-1 rounded-lg text-xs">RouterOS v6</button>
                            </div>
                        </div>
                        <div class="relative">
                            <pre class="bg-slate-950 text-emerald-400 font-mono text-[11px] p-4 rounded-xl overflow-x-auto border border-slate-800 leading-relaxed whitespace-pre-wrap"><code x-text="selected ? selected.scripts[ros] : ''"></code></pre>
                            <button type="button" @click="copy(selected?.scripts?.[ros] || '', 'Script disalin')"
                                    class="absolute top-3 right-3 px-3 py-1.5 bg-violet-600 hover:bg-violet-500 text-white font-semibold rounded-lg text-xs flex items-center gap-1.5">
                                <i class="fa-regular fa-copy"></i> Salin
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-sm">
                        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3">Kredensial</h3>
                        <div class="flex justify-between gap-2 text-xs">
                            <span class="text-slate-500 font-semibold">Username</span>
                            <span class="font-mono font-bold text-slate-900 text-right" x-text="selected?.username"></span>
                        </div>
                        <div class="flex justify-between gap-2 text-xs">
                            <span class="text-slate-500 font-semibold">Password</span>
                            <button type="button" class="font-mono font-bold text-violet-700" @click="copy(selected?.password, 'Password disalin')" x-text="selected?.password"></button>
                        </div>
                        <div class="flex justify-between gap-2 text-xs border-t border-slate-100 pt-3">
                            <span class="text-slate-500 font-semibold">Node</span>
                            <span class="font-mono font-bold" x-text="selected?.nodeHost"></span>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-3 shadow-sm">
                        <h3 class="font-bold text-slate-900 text-sm border-b border-slate-100 pb-3">Port Remote</h3>
                        <template x-for="m in (selected?.portMaps || [])" :key="m.remote_port">
                            <div class="flex items-center justify-between gap-2 text-xs py-2 border-b border-slate-50 last:border-0">
                                <div>
                                    <p class="font-bold text-slate-800" x-text="m.label"></p>
                                    <p class="font-mono text-[11px] text-violet-700" x-text="m.endpoint"></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-emerald-600 font-bold" x-text="':' + m.target_port"></p>
                                    <button type="button" @click="copy(m.endpoint, 'URL remote disalin')" class="text-[10px] font-bold text-slate-500 hover:text-violet-600">Salin</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div x-show="toast" x-transition
         class="fixed bottom-5 right-5 z-50 px-4 py-3 bg-slate-900 text-white rounded-xl text-sm font-medium shadow-xl"
         x-text="toast"></div>

    <script>
    function tunnelDash(rows) {
        return {
            tab: 'list',
            selected: null,
            ros: 'ros7',
            toast: '',
            rows: (rows || []).map(r => ({ ...r, showPass: false })),
            openManage(item) {
                this.selected = item;
                this.tab = 'detail';
                this.ros = 'ros7';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            copy(text, msg) {
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    this.toast = msg || 'Disalin';
                    setTimeout(() => this.toast = '', 1800);
                });
            },
        };
    }
    </script>
</body>
</html>
