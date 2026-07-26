@extends('layouts.dashboard')

@section('title', 'Kas Masuk/Keluar')
@section('heading', 'Kas Masuk/Keluar')

@push('styles')
<style>
/* Cashflow Page */
.cashflow-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
.cashflow-header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #1e293b; }
.btn-add-transaksi { padding: 12px 24px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35); transition: transform 0.2s, box-shadow 0.2s; }
.btn-add-transaksi:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); color: #fff; }
.cashflow-filter { background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 30px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.cashflow-filter .filter-group { display: flex; flex-direction: column; gap: 6px; }
.cashflow-filter label { font-size: 12px; font-weight: 600; color: #64748b; }
.cashflow-filter input, .cashflow-filter select { padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; min-width: 140px; }
.cashflow-filter input:focus, .cashflow-filter select:focus { border-color: #4D44B5; outline: none; }
.cashflow-filter .btn-filter { padding: 10px 20px; border: none; background: linear-gradient(135deg, #4D44B5 0%, #6366f1 100%); color: #fff; border-radius: 10px; font-weight: 600; cursor: pointer; }
.cashflow-filter .btn-reset { padding: 10px 20px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-block; }
.cashflow-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
.cashflow-stat { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 0 30px rgba(0,0,0,0.05); border-left: 4px solid; }
.cashflow-stat.masuk { border-left-color: #10b981; }
.cashflow-stat.keluar { border-left-color: #ef4444; }
.cashflow-stat.saldo { border-left-color: #4D44B5; }
.cashflow-stat .stat-label { font-size: 13px; color: #64748b; font-weight: 600; margin-bottom: 8px; }
.cashflow-stat .stat-value { font-size: 22px; font-weight: 700; color: #1e293b; }
.cashflow-table-wrap { background: #fff; border-radius: 16px; box-shadow: 0 0 30px rgba(0,0,0,0.05); overflow: hidden; }
.cashflow-table { width: 100%; border-collapse: collapse; }
.cashflow-table th { background: #f8fafc; padding: 14px 18px; text-align: left; font-weight: 600; color: #64748b; font-size: 13px; }
.cashflow-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; }
.cashflow-table tr:hover { background: #fafafa; }
.cashflow-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.cashflow-badge.masuk { background: #dcfce7; color: #15803d; }
.cashflow-badge.keluar { background: #fee2e2; color: #b91c1c; }
.btn-action-cash { width: 36px; height: 36px; border: none; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; margin-right: 6px; text-decoration: none; font-size: 14px; transition: all 0.2s; }
.btn-edit-cash { background: #e0e7ff; color: #4D44B5; }
.btn-edit-cash:hover { background: #4D44B5; color: #fff; }
.btn-delete-cash { background: #fee2e2; color: #dc2626; }
.btn-delete-cash:hover { background: #dc2626; color: #fff; }
/* Modal */
.cashflow-modal { display: none; position: fixed; inset: 0; z-index: 1100; align-items: center; justify-content: center; padding: 24px; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.25s ease; }
.cashflow-modal.show { display: flex; opacity: 1; }
.cashflow-modal-dialog { background: #fff; border-radius: 20px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.9); transition: transform 0.25s ease; }
.cashflow-modal.show .cashflow-modal-dialog { transform: scale(1); }
.cashflow-modal-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 24px 28px; border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; align-items: center; }
.cashflow-modal-header h3 { margin: 0; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.cashflow-modal-close { width: 40px; height: 40px; border: none; background: rgba(255,255,255,0.2); color: #fff; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s; }
.cashflow-modal-close:hover { background: rgba(255,255,255,0.3); color: #fff; }
.cashflow-modal-body { padding: 28px; }
.cashflow-form .form-group { margin-bottom: 20px; }
.cashflow-form label { display: block; font-weight: 600; color: #1e293b; margin-bottom: 8px; font-size: 14px; }
.cashflow-form input, .cashflow-form select { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; box-sizing: border-box; transition: border-color 0.2s; }
.cashflow-form input:focus, .cashflow-form select:focus { border-color: #10b981; outline: none; }
.cashflow-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.cashflow-modal-footer { padding: 20px 28px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end; background: #f8fafc; border-radius: 0 0 20px 20px; }
.cashflow-modal-footer .btn-simpan { padding: 14px 28px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border-radius: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.cashflow-modal-footer .btn-batal { padding: 14px 28px; border: 2px solid #e2e8f0; background: #fff; color: #64748b; border-radius: 12px; font-weight: 600; cursor: pointer; }
.form-error { font-size: 12px; color: #dc2626; margin-top: 4px; }
@media (max-width: 768px) {
.cashflow-stats { grid-template-columns: 1fr; }
.cashflow-form .form-row { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="cashflow-header">
    <h1><i class="fa fa-exchange"></i> Kas Masuk / Keluar</h1>
    <button type="button" class="btn-add-transaksi" id="btnOpenCashflowModal"><i class="fa fa-plus"></i> Tambah Transaksi</button>
</div>

<form method="GET" action="{{ route('keuangan.cashflow') }}" class="cashflow-filter">
    <div class="filter-group">
        <label>Dari Tanggal</label>
        <input type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}">
    </div>
    <div class="filter-group">
        <label>Sampai Tanggal</label>
        <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}">
    </div>
    <div class="filter-group">
        <label>Tipe</label>
        <select name="tipe">
            <option value="">Semua</option>
            <option value="masuk" {{ request('tipe') === 'masuk' ? 'selected' : '' }}>Masuk</option>
            <option value="keluar" {{ request('tipe') === 'keluar' ? 'selected' : '' }}>Keluar</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Kategori</label>
        <select name="kategori">
            <option value="">Semua</option>
            @foreach(array_merge(\App\Models\CashTransaction::kategoriMasuk(), \App\Models\CashTransaction::kategoriKeluar()) as $k => $v)
            <option value="{{ $k }}" {{ request('kategori') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-group">
        <button type="submit" class="btn-filter"><i class="fa fa-search"></i> Filter</button>
    </div>
    <div class="filter-group">
        <a href="{{ route('keuangan.cashflow') }}" class="btn-reset">Reset</a>
    </div>
</form>

<div class="cashflow-stats">
    <div class="cashflow-stat masuk">
        <div class="stat-label">Total Masuk</div>
        <div class="stat-value">{{ $currency }} {{ number_format($totalMasuk, 0, ',', '.') }}</div>
    </div>
    <div class="cashflow-stat keluar">
        <div class="stat-label">Total Keluar</div>
        <div class="stat-value">{{ $currency }} {{ number_format($totalKeluar, 0, ',', '.') }}</div>
    </div>
    <div class="cashflow-stat saldo">
        <div class="stat-label">Saldo Periode</div>
        <div class="stat-value">{{ $currency }} {{ number_format($saldoPeriod, 0, ',', '.') }}</div>
    </div>
</div>

<div class="cashflow-table-wrap">
    <div class="overflow" style="max-height: 55vh;">
        <table class="cashflow-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th class="text-right">Nominal</th>
                    <th>Metode</th>
                    <th>Keterangan</th>
                    <th class="text-center" style="width:100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                    <td>
                        <span class="cashflow-badge {{ $item->tipe }}">{{ $item->tipe === 'masuk' ? 'Masuk' : 'Keluar' }}</span>
                    </td>
                    <td>
                        {{ $item->kategori_label }}
                        @if($item->isFromPppPayment())
                        <span style="background:#e0e7ff;color:#4D44B5;font-size:10px;padding:2px 6px;border-radius:6px;margin-left:6px;" title="Dari Tagihan PPP">PPP</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-weight:600; color:{{ $item->tipe === 'masuk' ? '#15803d' : '#b91c1c' }};">
                        {{ $item->tipe === 'masuk' ? '+' : '-' }}{{ $currency }} {{ number_format($item->nominal, 0, ',', '.') }}
                    </td>
                    <td>{{ $item->metode_label }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                    <td class="text-center">
                        <a href="{{ route('keuangan.cashflow.edit', $item) }}" class="btn-action-cash btn-edit-cash" title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('keuangan.cashflow.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus transaksi ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action-cash btn-delete-cash" title="Hapus"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding:48px; color:#94a3b8;">Belum ada transaksi. Klik <strong>Tambah Transaksi</strong> untuk menambah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($items->hasPages())
<div style="margin-top:20px;">{{ $items->links() }}</div>
@endif

{{-- Modal Tambah Transaksi --}}
<div id="cashflowModal" class="cashflow-modal">
    <div class="cashflow-modal-dialog">
        <div class="cashflow-modal-header">
            <h3><i class="fa fa-plus-circle"></i> Tambah Transaksi Baru</h3>
            <button type="button" class="cashflow-modal-close" id="btnCloseCashflowModal"><i class="fa fa-times"></i></button>
        </div>
        <form action="{{ route('keuangan.cashflow.store') }}" method="POST" class="cashflow-form">
            @csrf
            <div class="cashflow-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                        @error('tanggal')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select name="tipe" id="tipeTambah" required>
                            <option value="masuk" {{ old('tipe') !== 'keluar' ? 'selected' : '' }}>Masuk</option>
                            <option value="keluar" {{ old('tipe') === 'keluar' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori" id="kategoriTambah" required></select>
                    @error('kategori')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nominal ({{ $currency }})</label>
                        <input type="number" name="nominal" min="1" value="{{ old('nominal') }}" placeholder="0" required>
                        @error('nominal')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Metode</label>
                        <select name="metode">
                            @foreach(\App\Models\CashTransaction::metodeList() as $k => $v)
                            <option value="{{ $k }}" {{ old('metode', 'tunai') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" placeholder="Opsional">
                    @error('keterangan')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="cashflow-modal-footer">
                <button type="button" class="btn-batal" id="btnCancelCashflowModal">Batal</button>
                <button type="submit" class="btn-simpan"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var modal = document.getElementById('cashflowModal');
    var btnOpen = document.getElementById('btnOpenCashflowModal');
    var btnClose = document.getElementById('btnCloseCashflowModal');
    var btnCancel = document.getElementById('btnCancelCashflowModal');

    var tipeSel = document.getElementById('tipeTambah');
    var katSel = document.getElementById('kategoriTambah');
    if (!tipeSel || !katSel) return;

    var optMasuk = @json(array_keys(\App\Models\CashTransaction::kategoriMasuk()));
    var optKeluar = @json(array_keys(\App\Models\CashTransaction::kategoriKeluar()));
    var labels = @json(array_merge(\App\Models\CashTransaction::kategoriMasuk(), \App\Models\CashTransaction::kategoriKeluar()));
    var oldKategori = @json(old('kategori'));

    function syncKategori() {
        var tipe = tipeSel.value;
        var opts = tipe === 'masuk' ? optMasuk : optKeluar;
        var sel = katSel.value || oldKategori;
        katSel.innerHTML = '';
        opts.forEach(function(k) {
            var o = document.createElement('option');
            o.value = k;
            o.textContent = labels[k] || k;
            if (k === sel || (opts.indexOf(sel) === -1 && opts[0] === k)) o.selected = true;
            katSel.appendChild(o);
        });
        oldKategori = null;
    }

    function openModal() {
        if (!modal) return;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    btnOpen && btnOpen.addEventListener('click', openModal);
    btnClose && btnClose.addEventListener('click', closeModal);
    btnCancel && btnCancel.addEventListener('click', closeModal);
    modal && modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeModal();
    });

    tipeSel.addEventListener('change', syncKategori);
    syncKategori();

    @if($errors->any())
    openModal();
    @endif
})();
</script>
@endpush
@endsection
