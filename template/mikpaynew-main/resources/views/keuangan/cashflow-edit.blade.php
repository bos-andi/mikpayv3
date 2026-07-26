@extends('layouts.dashboard')

@section('title', 'Edit Transaksi Kas')
@section('heading', 'Edit Transaksi Kas')

@section('content')
<div class="row">
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-edit"></i> Edit Transaksi</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('keuangan.cashflow.update', $cashflow) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $cashflow->tanggal->format('Y-m-d')) }}" required>
                        @error('tanggal')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Tipe</label>
                        <select name="tipe" id="tipeEdit" class="form-control" required>
                            <option value="masuk" {{ old('tipe', $cashflow->tipe) === 'masuk' ? 'selected' : '' }}>Masuk</option>
                            <option value="keluar" {{ old('tipe', $cashflow->tipe) === 'keluar' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" id="kategoriEdit" class="form-control" required>
                            @foreach($cashflow->tipe === 'masuk' ? \App\Models\CashTransaction::kategoriMasuk() : \App\Models\CashTransaction::kategoriKeluar() as $k => $v)
                            <option value="{{ $k }}" {{ old('kategori', $cashflow->kategori) === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('kategori')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Nominal ({{ $currentRouter->currency ?? 'Rp' }})</label>
                        <input type="number" name="nominal" class="form-control" min="1" value="{{ old('nominal', $cashflow->nominal) }}" required>
                        @error('nominal')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label>Metode</label>
                        <select name="metode" class="form-control">
                            @foreach(\App\Models\CashTransaction::metodeList() as $k => $v)
                            <option value="{{ $k }}" {{ old('metode', $cashflow->metode) === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="{{ old('keterangan', $cashflow->keterangan) }}" placeholder="Opsional">
                        @error('keterangan')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                        <a href="{{ route('keuangan.cashflow') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var tipeSel = document.getElementById('tipeEdit');
    var katSel = document.getElementById('kategoriEdit');
    if (!tipeSel || !katSel) return;

    var optMasuk = @json(array_keys(\App\Models\CashTransaction::kategoriMasuk()));
    var optKeluar = @json(array_keys(\App\Models\CashTransaction::kategoriKeluar()));
    var labels = @json(array_merge(\App\Models\CashTransaction::kategoriMasuk(), \App\Models\CashTransaction::kategoriKeluar()));
    var currentKat = @json(old('kategori', $cashflow->kategori));

    function syncKategori() {
        var tipe = tipeSel.value;
        var opts = tipe === 'masuk' ? optMasuk : optKeluar;
        katSel.innerHTML = '';
        var sel = opts.indexOf(currentKat) >= 0 ? currentKat : opts[0];
        opts.forEach(function(k) {
            var o = document.createElement('option');
            o.value = k;
            o.textContent = labels[k] || k;
            if (k === sel) o.selected = true;
            katSel.appendChild(o);
        });
    }
    tipeSel.addEventListener('change', function() { currentKat = null; syncKategori(); });
    syncKategori();
})();
</script>
@endpush
@endsection
