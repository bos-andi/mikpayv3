@extends('layouts.dashboard')

@section('title', 'IP Bindings')
@section('heading', 'IP Bindings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-address-book"></i> IP Bindings
                {{ count($bindings) }} item
                </h3>
            </div>
            <div class="card-body">
                <input type="text" id="filterTable" class="form-control w-50 mb-3" placeholder="Cari...">
                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>MAC Address</th>
                                <th>Address</th>
                                <th>To Address</th>
                                <th>Server</th>
                                <th>Disabled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bindings as $b)
                            <tr>
                                <td>{{ $b['comment'] ?? $b['.id'] ?? '-' }}</td>
                                <td>{{ $b['mac-address'] ?? '-' }}</td>
                                <td>{{ $b['address'] ?? '-' }}</td>
                                <td>{{ $b['to-address'] ?? '-' }}</td>
                                <td>{{ $b['server'] ?? '-' }}</td>
                                <td>{{ $b['disabled'] ?? 'no' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted">Tidak ada binding.</td></tr>
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
