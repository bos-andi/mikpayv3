@extends('layouts.dashboard')

@section('title', 'Daftar User Hotspot')
@section('heading', 'Daftar User')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-users"></i> Daftar User
                    <span style="font-size: 14px">
                        | <a href="{{ route('hotspot.add-user') }}"><i class="fa fa-user-plus"></i> Tambah User</a>
                        | <a href="{{ route('hotspot.generate-user') }}"><i class="fa fa-magic"></i> Generate User</a>
                    </span>
                </h3>
            </div>
            <div class="card-body">
                {{-- Filter bar seperti template: Cari + Profile + Comment --}}
                <div class="row align-items-center mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="filterTable" class="form-control" placeholder="Cari..." title="Cari di tabel">
                            <select class="form-control" style="max-width: 180px;" onchange="location = this.value;" title="Filter by Profile">
                                <option value="">Profile</option>
                                <option value="{{ route('hotspot.users', ['profile' => 'all']) }}" {{ ($filterProfile ?? '') === 'all' ? 'selected' : '' }}>Tampilkan Semua</option>
                                @foreach($profiles as $p)
                                <option value="{{ route('hotspot.users', ['profile' => $p['name'] ?? '']) }}" {{ ($filterProfile ?? '') === ($p['name'] ?? '') ? 'selected' : '' }}>{{ $p['name'] ?? '' }}</option>
                                @endforeach
                            </select>
                            <select id="commentFilter" class="form-control" style="max-width: 220px;" onchange="var v=this.value; if(v) location='{{ route('hotspot.users') }}?comment='+encodeURIComponent(v);" title="Filter by Comment">
                                <option value="">Comment</option>
                                @foreach($commentOptions ?? [] as $opt)
                                <option value="{{ $opt['comment'] }}" {{ ($filterComment ?? '') === ($opt['comment'] ?? '') ? 'selected' : '' }}>{{ $opt['comment'] }} {{ $opt['profile'] }} [{{ $opt['count'] }}]</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        @if(!empty($filterComment))
                        <a href="{{ route('hotspot.users.remove-by-comment', ['comment' => $filterComment]) }}" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus semua user dengan comment {{ $filterComment }}?');"><i class="fa fa-trash"></i> Hapus by Comment</a>
                        @elseif(!empty($filterExp))
                        <a href="{{ route('hotspot.users.remove-expired') }}" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus semua user expired?');"><i class="fa fa-trash"></i> User Expired</a>
                        @endif
                        <button type="button" class="btn btn-primary btn-sm" title="Cetak voucher (pilih comment di filter dulu)" onclick="var c=document.getElementById('commentFilter').value; if(!c){ alert('Pilih Comment di filter terlebih dulu.'); return; } window.open('{{ route('hotspot.voucher.print') }}?id='+encodeURIComponent(c)+'&qr=no', '_blank');"><i class="fa fa-print"></i> Cetak</button>
                        <button type="button" class="btn btn-primary btn-sm" title="Cetak voucher QR" onclick="var c=document.getElementById('commentFilter').value; if(!c){ alert('Pilih Comment di filter terlebih dulu.'); return; } window.open('{{ route('hotspot.voucher.print') }}?id='+encodeURIComponent(c)+'&qr=yes', '_blank');"><i class="fa fa-qrcode"></i> Cetak QR</button>
                        <button type="button" class="btn btn-primary btn-sm" title="Cetak kecil" onclick="var c=document.getElementById('commentFilter').value; if(!c){ alert('Pilih Comment di filter terlebih dulu.'); return; } window.open('{{ route('hotspot.voucher.print') }}?id='+encodeURIComponent(c)+'&small=yes', '_blank');"><i class="fa fa-print"></i> Cetak Small</button>
                    </div>
                </div>

                <div class="overflow box-bordered" style="max-height: 75vh;">
                    <table id="dataTable" class="table table-bordered table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 90px;">{{ $totalCount ?? count($users) }}</th>
                                <th>Server</th>
                                <th>Name</th>
                                <th class="text-center">Print</th>
                                <th>Profile</th>
                                <th>Mac Address</th>
                                <th class="text-right">Uptime</th>
                                <th class="text-right">Bytes In</th>
                                <th class="text-right">Bytes Out</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $u)
                            @php
                                $uid = $u['.id'] ?? '';
                                $uname = $u['name'] ?? '-';
                                $ucomment = $u['comment'] ?? '';
                                $udisabled = $u['disabled'] ?? '';
                                $utimelimit = $u['limit-uptime'] ?? '';
                                $udatalimit = isset($u['limit-bytes-total']) ? \App\Helpers\MikrotikFormat::formatBytes((int)$u['limit-bytes-total'], 2) : '';
                                $commentPre = substr($ucomment, 0, 3);
                                $isVoucherComment = ($commentPre === 'vc-' || $commentPre === 'up-') && is_numeric(substr($ucomment, 3, 3));
                                $isExpired = ($utimelimit === '1s');
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <a href="{{ route('hotspot.users.remove', ['id' => $uid]) }}{{ request()->hasAny(['profile','comment','exp']) ? '?'.http_build_query(request()->only(['profile','comment','exp'])) : '' }}" class="text-danger" onclick="return confirm('Yakin hapus user {{ $uname }}?');" title="Hapus"><i class="fa fa-minus-square"></i></a>
                                    @if($udisabled === 'true')
                                    <a href="{{ route('hotspot.users.enable', ['id' => $uid]) }}{{ request()->hasAny(['profile','comment','exp']) ? '?'.http_build_query(request()->only(['profile','comment','exp'])) : '' }}" class="text-warning" title="Aktifkan"><i class="fa fa-lock"></i></a>
                                    @else
                                    <a href="{{ route('hotspot.users.disable', ['id' => $uid]) }}{{ request()->hasAny(['profile','comment','exp']) ? '?'.http_build_query(request()->only(['profile','comment','exp'])) : '' }}" title="Nonaktifkan"><i class="fa fa-unlock"></i></a>
                                    @endif
                                </td>
                                <td>{{ $u['server'] ?? '-' }}</td>
                                <td><i class="fa fa-edit"></i> {{ $uname }}</td>
                                <td class="text-center">
                                    @php $voucherUser = (($u['password'] ?? '') === $uname ? 'vc' : 'up') . '-' . $uname; @endphp
                                    <a href="{{ route('hotspot.voucher.print', ['user' => $voucherUser, 'qr' => 'no']) }}" target="_blank" title="Cetak"><i class="fa fa-print"></i></a>
                                    <a href="{{ route('hotspot.voucher.print', ['user' => $voucherUser, 'qr' => 'yes']) }}" target="_blank" title="Cetak QR"><i class="fa fa-qrcode"></i></a>
                                </td>
                                <td>{{ $u['profile'] ?? '-' }}</td>
                                <td>{{ $u['mac-address'] ?? '-' }}</td>
                                <td class="text-right">{{ \App\Helpers\MikrotikFormat::formatUptime($u['uptime'] ?? '') }}</td>
                                <td class="text-right">{{ isset($u['bytes-in']) ? \App\Helpers\MikrotikFormat::formatBytes((int)$u['bytes-in'], 2) : '-' }}</td>
                                <td class="text-right">{{ isset($u['bytes-out']) ? \App\Helpers\MikrotikFormat::formatBytes((int)$u['bytes-out'], 2) : '-' }}</td>
                                <td>
                                    @if($isVoucherComment)
                                    <a href="{{ route('hotspot.users', ['comment' => $ucomment]) }}" title="Filter by {{ $ucomment }}"><i class="fa fa-search"></i> {{ $ucomment }} {{ $udatalimit }} {{ $utimelimit === '1s' ? ' expired' : ' ' . $utimelimit }}</a>
                                    @elseif($isExpired)
                                    <a href="{{ route('hotspot.users', ['profile' => 'all', 'exp' => '1']) }}" title="Filter expired"><i class="fa fa-search"></i> {{ $ucomment }} {{ $udatalimit }} expired</a>
                                    @else
                                    {{ $ucomment }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="10" class="text-center text-muted">Tidak ada user.</td></tr>
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
