@extends('layouts.dashboard')

@section('title', 'Hotspot Active')
@section('heading', 'Hotspot Active')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-wifi"></i> Hotspot Active
                @if($filterServer)
                    {{ $filterServer }} |
                    <a href="{{ route('hotspot.active') }}"><i class="fa fa-search"></i> Tampilkan Semua</a>
                @else
                    {{ count($actives) }} item
                @endif
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Server</th>
                                <th>User</th>
                                <th>Address</th>
                                <th>Mac Address</th>
                                <th class="text-right">Uptime</th>
                                <th class="text-right">Bytes In</th>
                                <th class="text-right">Bytes Out</th>
                                <th class="text-right">Time Left</th>
                                <th>Login By</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($actives as $a)
                            <tr>
                                <td>{{ $a['server'] ?? '-' }}</td>
                                <td>{{ $a['user'] ?? '-' }}</td>
                                <td>{{ $a['address'] ?? '-' }}</td>
                                <td>{{ $a['mac-address'] ?? '-' }}</td>
                                <td class="text-right">{{ \App\Helpers\MikrotikFormat::formatUptime($a['uptime'] ?? '') }}</td>
                                <td class="text-right">{{ isset($a['bytes-in']) ? \App\Helpers\MikrotikFormat::formatBytes((int)$a['bytes-in'], 2) : '-' }}</td>
                                <td class="text-right">{{ isset($a['bytes-out']) ? \App\Helpers\MikrotikFormat::formatBytes((int)$a['bytes-out'], 2) : '-' }}</td>
                                <td class="text-right">{{ \App\Helpers\MikrotikFormat::formatUptime($a['session-time-left'] ?? '') }}</td>
                                <td>{{ $a['login-by'] ?? '-' }}</td>
                                <td>{{ $a['comment'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center text-muted">Tidak ada sesi aktif.</td></tr>
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
