@extends('layouts.dashboard')

@section('title', 'Hosts')
@section('heading', 'Hosts')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-laptop"></i> Hosts
                {{ count($hosts) }} item
                | <a href="{{ route('hotspot.hosts') }}">Semua</a>
                | <a href="{{ route('hotspot.hosts', ['filter' => 'hosta']) }}">Authorized</a>
                | <a href="{{ route('hotspot.hosts', ['filter' => 'hostp']) }}">Bypassed</a>
                | <i class="fa fa-refresh pointer" onclick="location.reload();" title="Reload"></i>
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>MAC Address</th>
                                <th>Address</th>
                                <th>To Address</th>
                                <th>Server</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hosts as $h)
                            <tr>
                                <td>{{ $h['mac-address'] ?? '-' }}</td>
                                <td>{{ $h['address'] ?? '-' }}</td>
                                <td>{{ $h['to-address'] ?? '-' }}</td>
                                <td>{{ $h['server'] ?? '-' }}</td>
                                <td>{{ $h['comment'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada host.</td></tr>
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
