@extends('layouts.dashboard')

@section('title', 'PPP Active')
@section('heading', 'PPP Active')

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-wifi"></i> PPP Active — {{ count($actives) }} item</h3>
    </div>
    <div class="card-body">
        <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
        <div class="overflow box-bordered" style="max-height: 75vh;">
            <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Caller ID</th>
                        <th>Address</th>
                        <th class="text-right">Uptime</th>
                        <th>Encoding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actives as $i => $a)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $a['name'] ?? '-' }}</td>
                        <td>{{ $a['service'] ?? '-' }}</td>
                        <td>{{ $a['caller-id'] ?? '-' }}</td>
                        <td>{{ $a['address'] ?? '-' }}</td>
                        <td class="text-right">{{ \App\Helpers\MikrotikFormat::formatUptime($a['uptime'] ?? '') }}</td>
                        <td>{{ $a['encoding'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">Tidak ada koneksi PPP aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
