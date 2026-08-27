@extends('layouts.panel')

@section('title', 'Peta GIS & ODP — MikPay')
@section('page', 'gis')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pemetaan Jaringan Fiber Optik (GIS)</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Hierarki OLT → ODC → ODP → pelanggan PPPoE
            @if (!empty($router))
                · Router: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $router->displayName() }}</span>
            @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" id="gisBtnAddOlt" class="bg-amber-500 hover:bg-amber-400 text-xs px-3.5 py-2 rounded-xl font-semibold transition text-slate-950 shadow-sm">
            <i class="fa-solid fa-tower-cell mr-1"></i> Tambah OLT
        </button>
        <button type="button" id="gisBtnAddOdc" class="bg-violet-600 hover:bg-violet-500 text-xs px-3.5 py-2 rounded-xl font-semibold transition text-white shadow-sm">
            <i class="fa-solid fa-cube mr-1"></i> Tambah ODC
        </button>
        <button type="button" id="gisBtnAddOdp" class="bg-indigo-600 hover:bg-indigo-500 text-xs px-3.5 py-2 rounded-xl font-semibold transition text-white shadow-sm">
            <i class="fa-solid fa-plus mr-1"></i> Tambah ODP
        </button>
        <button type="button" id="gisBtnCancelMode" class="hidden bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-xs px-3.5 py-2 rounded-xl font-semibold transition text-slate-800 dark:text-slate-200">
            Batal
        </button>
    </div>
</div>

<div id="gisModeBanner" class="hidden rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-900 text-xs font-semibold px-4 py-3 flex flex-wrap items-center justify-between gap-2">
    <span id="gisModeBannerText">Mode penempatan aktif — klik lokasi di peta, geser pin untuk akurasi.</span>
    <div class="flex flex-wrap items-center gap-2">
        <span id="gisPlaceCoords" class="font-mono text-[10px] font-normal text-indigo-800/80">—</span>
        <button type="button" id="gisBtnConfirmPlace" class="hidden bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] px-3 py-1.5 rounded-lg font-bold shadow-sm">
            Gunakan titik ini
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[650px]">
    <div class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden relative shadow-sm">
        <div id="gisMap" class="w-full h-full touch-none"></div>
        <div id="gisCursorHud" class="hidden absolute bottom-4 right-4 z-[1000] bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl text-[10px] font-mono font-semibold text-slate-700 dark:text-slate-200 shadow-md pointer-events-none">
            —
        </div>

        <div class="absolute bottom-4 left-4 z-[1000] bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 p-3 rounded-xl text-[11px] space-y-1.5 shadow-xl">
            <p class="font-bold text-slate-800 dark:text-slate-200 mb-1">Legenda:</p>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>
                <span class="text-slate-700 dark:text-slate-300">Server OLT</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-violet-500 inline-block"></span>
                <span class="text-slate-700 dark:text-slate-300">ODC (Cabinet)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-cyan-400 inline-block"></span>
                <span class="text-slate-700 dark:text-slate-300">Kotak ODP</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400 inline-block"></span>
                <span class="text-slate-700 dark:text-slate-300">Pelanggan online</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                <span class="text-slate-700 dark:text-slate-300">Isolir / offline</span>
            </div>
        </div>

        <div id="gisStatsChip" class="absolute top-4 left-4 z-[1000] bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-md">
            Memuat peta…
        </div>

        <div class="absolute top-4 right-4 z-[1000] flex flex-col gap-2">
            <button type="button" id="gisBtnLocate" title="Lokasi GPS saya" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 w-10 h-10 rounded-xl shadow-md flex items-center justify-center text-indigo-600 dark:text-indigo-400 transition">
                <i class="fa-solid fa-location-crosshairs"></i>
            </button>
            <button type="button" id="gisBtnBasemap" title="Ganti basemap (jalan / satelit)" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 w-10 h-10 rounded-xl shadow-md flex items-center justify-center text-slate-600 dark:text-slate-300 transition">
                <i class="fa-solid fa-layer-group"></i>
            </button>
            <button type="button" id="gisBtnZoomPlace" title="Perbesar untuk penempatan akurat" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 w-10 h-10 rounded-xl shadow-md flex items-center justify-center text-slate-600 dark:text-slate-300 transition">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </button>
        </div>
    </div>

    <div id="gisInspector" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 flex flex-col overflow-y-auto shadow-sm">
        <div id="gisInspectorEmpty" class="flex-1 flex flex-col items-center justify-center text-center px-3 py-8">
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-600 flex items-center justify-center mb-3">
                <i class="fa-solid fa-map-location-dot text-lg"></i>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-slate-100">Inspector Jaringan</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                Klik marker OLT / ODC / ODP di peta.
                Urutan ideal: <strong>OLT → ODC → ODP</strong>, lalu assign pelanggan di port ODP.
            </p>
        </div>

        <div id="gisInspectorBody" class="hidden flex-1 flex flex-col space-y-4">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-slate-100 truncate" id="gisInspTitle">—</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono mt-0.5" id="gisInspCode">—</p>
                </div>
                <span id="gisInspBadge" class="text-[10px] bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 px-2 py-0.5 rounded-full font-mono font-bold shrink-0">ODP</span>
            </div>

            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/50 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500 dark:text-slate-400 gap-2">
                    <span>Terhubung ke</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold text-right" id="gisInspParent">—</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 gap-2">
                    <span>PON / uplink</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold text-right" id="gisInspPon">—</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 gap-2">
                    <span>Rasio splitter</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold" id="gisInspRatio">—</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 gap-2">
                    <span>Kapasitas</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold" id="gisInspCapacity">—</span>
                </div>
                <div class="flex justify-between text-slate-500 dark:text-slate-400 gap-2">
                    <span>Koordinat</span>
                    <span class="text-slate-800 dark:text-slate-200 font-mono text-[10px]" id="gisInspCoords">—</span>
                </div>
            </div>

            <div id="gisPortsWrap" class="space-y-2">
                <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300" id="gisPortsTitle">Mapping Port</h4>
                <div id="gisPortsGrid" class="grid grid-cols-1 gap-2 text-xs"></div>
            </div>

            <div class="mt-auto pt-2 space-y-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button" id="gisBtnMoveNode" class="w-full bg-cyan-50 dark:bg-cyan-500/10 hover:bg-cyan-100 dark:hover:bg-cyan-500/20 text-xs py-2 rounded-xl font-medium border border-cyan-200 dark:border-cyan-500/20 transition text-cyan-800 dark:text-cyan-300">
                    <i class="fa-solid fa-up-down-left-right mr-1"></i> Pindahkan di peta
                </button>
                <button type="button" id="gisBtnEditNode" class="w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs py-2 rounded-xl font-medium border border-slate-200 dark:border-slate-700 transition text-slate-800 dark:text-slate-200">
                    <i class="fa-solid fa-pen mr-1"></i> Edit detail
                </button>
                <button type="button" id="gisBtnDeleteNode" class="w-full bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 text-xs py-2 rounded-xl font-medium border border-rose-200 dark:border-rose-500/20 transition text-rose-700 dark:text-rose-300">
                    <i class="fa-solid fa-trash mr-1"></i> Hapus dari peta
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: tambah / edit node --}}
<div id="gisNodeModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-[5000] hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-5 space-y-4 relative z-[5001] max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between">
            <h3 id="gisNodeModalTitle" class="font-bold text-sm text-slate-900 dark:text-white">Tambah ODP</h3>
            <button type="button" data-gis-close-node class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="gisNodeForm" class="space-y-3 text-xs">
            <input type="hidden" name="type" id="gisNodeType" value="odp">
            <input type="hidden" name="latitude" id="gisNodeLat">
            <input type="hidden" name="longitude" id="gisNodeLng">
            <div>
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Kode *</label>
                <input type="text" id="gisNodeCode" name="code" required maxlength="64" placeholder="ODP-BLOK-A1" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100 font-mono">
            </div>
            <div>
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Nama (opsional)</label>
                <input type="text" id="gisNodeName" name="name" maxlength="120" placeholder="Depan Masjid RT 03" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100">
            </div>
            <div id="gisNodeParentWrap">
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Terhubung ke (parent)</label>
                <select id="gisNodeParent" name="parent_id" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100">
                    <option value="">— Tidak di-set —</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1" id="gisNodeParentHint">ODP → ODC/OLT · ODC → OLT</p>
            </div>
            <div id="gisNodePortCountWrap">
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Rasio splitter *</label>
                <select id="gisNodePortCount" name="port_count" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100">
                    <option value="2">1 : 2</option>
                    <option value="4">1 : 4</option>
                    <option value="8" selected>1 : 8</option>
                    <option value="16">1 : 16</option>
                    <option value="32">1 : 32</option>
                    <option value="64">1 : 64</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-500 dark:text-slate-400 mb-1">PON / uplink (opsional)</label>
                <input type="text" id="gisNodePon" name="pon_label" maxlength="100" placeholder="PON 1 — HSGQ" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Catatan</label>
                <textarea id="gisNodeNotes" name="notes" rows="2" maxlength="2000" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100"></textarea>
            </div>
            <p class="text-[10px] text-slate-400 font-mono" id="gisNodeCoordHint">Lat/Lng: —</p>
            <div class="flex gap-2 pt-1">
                <button type="button" data-gis-close-node class="flex-1 bg-slate-100 dark:bg-slate-800 py-2.5 rounded-xl font-semibold text-slate-700 dark:text-slate-200">Batal</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-2.5 rounded-xl font-bold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: assign port --}}
<div id="gisPortModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-[5000] hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-5 space-y-4 relative z-[5001]">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Assign Port <span id="gisPortModalNum" class="font-mono text-cyan-600"></span></h3>
            <button type="button" data-gis-close-port class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="gisPortForm" class="space-y-3 text-xs">
            <input type="hidden" id="gisPortNumber" name="port_number">
            <div>
                <label class="block text-slate-500 dark:text-slate-400 mb-1">Username PPPoE</label>
                <input type="text" id="gisPortCustomer" name="customer_name" list="gisCustomerList" autocomplete="off" placeholder="username@an.net" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100 font-mono">
                <datalist id="gisCustomerList"></datalist>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-slate-500 dark:text-slate-400 mb-1">Lat pelanggan</label>
                    <input type="number" step="any" id="gisPortLat" name="latitude" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100 font-mono">
                </div>
                <div>
                    <label class="block text-slate-500 dark:text-slate-400 mb-1">Lng pelanggan</label>
                    <input type="number" step="any" id="gisPortLng" name="longitude" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-900 dark:text-slate-100 font-mono">
                </div>
            </div>
            <button type="button" id="gisBtnPickPortLoc" class="w-full bg-cyan-50 dark:bg-cyan-500/10 hover:bg-cyan-100 dark:hover:bg-cyan-500/20 text-cyan-800 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-500/20 rounded-xl py-2 font-semibold">
                <i class="fa-solid fa-location-crosshairs mr-1"></i> Ambil titik dari peta
            </button>
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" data-gis-close-port class="flex-1 bg-slate-100 dark:bg-slate-800 py-2.5 rounded-xl font-semibold text-slate-700 dark:text-slate-200">Batal</button>
                <button type="button" id="gisBtnClearPort" class="px-3 py-2.5 rounded-xl font-semibold text-rose-700 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20">Kosongkan</button>
                <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-2.5 rounded-xl font-bold text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.__gisConfig = @json($gisConfig ?? []);
</script>
<script src="{{ asset('js/panel-gis.js') }}?v=7" defer></script>
@endpush
