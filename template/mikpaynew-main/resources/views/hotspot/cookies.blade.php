@extends('layouts.dashboard')

@section('title', 'Hotspot Cookies')
@section('heading', 'Hotspot Cookies')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-hourglass"></i> Hotspot Cookies
                {{ count($cookies) }} item
                | <i class="fa fa-refresh pointer" onclick="location.reload();" title="Reload"></i>
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>MAC Address</th>
                                <th>Domain</th>
                                <th>Expires In</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cookies as $c)
                            <tr>
                                <td>{{ $c['user'] ?? '-' }}</td>
                                <td>{{ $c['mac-address'] ?? '-' }}</td>
                                <td>{{ $c['domain'] ?? '-' }}</td>
                                <td>{{ \App\Helpers\MikrotikFormat::formatUptime($c['expires-in'] ?? '') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada cookie.</td></tr>
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
