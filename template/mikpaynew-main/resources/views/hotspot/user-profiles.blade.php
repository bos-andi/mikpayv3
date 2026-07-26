@extends('layouts.dashboard')

@section('title', 'User Profile')
@section('heading', 'User Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-pie-chart"></i> User Profile
                | <a href="{{ route('hotspot.user-profiles.add') }}"><i class="fa fa-user-plus"></i> Tambah</a>
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th class="text-center">{{ count($profiles) }}</th>
                                <th>Name</th>
                                <th>Shared Users</th>
                                <th>Rate Limit</th>
                                <th>Validity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profiles as $p)
                            <tr>
                                <td class="text-center"><a href="{{ route('hotspot.users', ['profile' => $p['name'] ?? '']) }}"><i class="fa fa-users"></i></a></td>
                                <td>{{ $p['name'] ?? '-' }}</td>
                                <td>{{ $p['shared-users'] ?? '-' }}</td>
                                <td>{{ $p['rate-limit'] ?? '-' }}</td>
                                <td>{{ $p['session-timeout'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada profile.</td></tr>
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
