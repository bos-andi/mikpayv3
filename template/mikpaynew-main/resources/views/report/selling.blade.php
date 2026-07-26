@extends('layouts.dashboard')

@section('title', 'Report Penjualan')
@section('heading', 'Report Penjualan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-money"></i> Report Penjualan Hotspot
                — Bulan: {{ $idbl }} — Total: {{ $currency }} {{ number_format($totalMonth, 0, ',', '.') }}
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>User</th>
                                <th class="text-right">Price ({{ $currency }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr>
                                <td>{{ $item['date'] }}</td>
                                <td>{{ $item['time'] }}</td>
                                <td>{{ $item['user'] }}</td>
                                <td class="text-right">{{ number_format($item['price'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada data penjualan untuk periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('filterTable')?.addEventListener('keyup', function() {
    var v = this.value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().indexOf(v) > -1 ? '' : 'none';
    });
});
</script>
@endpush
@endsection
