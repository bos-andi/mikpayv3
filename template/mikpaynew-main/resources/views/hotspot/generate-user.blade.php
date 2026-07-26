@extends('layouts.dashboard')

@section('title', 'Generate User')
@section('heading', 'Generate User')
@section('main_container_class', 'mc-subscription-full')

@push('styles')
<style>
@media (min-width: 993px) {
    #main .main-container.mc-subscription-full,
    .main-container.mc-subscription-full {
        max-width: none !important;
        width: 100% !important;
        margin-left: 10px !important;
        margin-right: 10px !important;
    }
}
</style>
@endpush

@section('content')
@php
    $last = $lastGenerate ?? null;
    $printComment = request('comment', $last['comment'] ?? '');
    $profileForList = request('profile', $last['profile'] ?? 'all');
@endphp
<div class="row">
    <div class="col-lg-8">
        <div class="card box-bordered">
            <div class="card-header">
                <h3><i class="fa fa-user-plus"></i> Generate Voucher</h3>
            </div>
            <div class="card-body">
                <form autocomplete="off" method="POST" action="{{ route('hotspot.generate-user.store') }}">
                    @csrf
                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <a class="btn bg-warning" href="{{ route('hotspot.users', ['profile' => 'all']) }}"><i class="fa fa-close"></i> Tutup</a>
                        <a class="btn bg-pink" href="{{ route('hotspot.users', ['profile' => $profileForList]) }}"><i class="fa fa-users"></i> User List</a>
                        <button type="submit" class="btn bg-primary"><i class="fa fa-save"></i> Generate</button>
                        @if($printComment !== '')
                            <a class="btn bg-secondary" href="{{ route('hotspot.voucher.print', ['id' => $printComment, 'qr' => 'no']) }}" target="_blank"><i class="fa fa-print"></i> Print</a>
                            <a class="btn bg-danger" href="{{ route('hotspot.voucher.print', ['id' => $printComment, 'qr' => 'yes']) }}" target="_blank"><i class="fa fa-qrcode"></i> Print QR</a>
                            <a class="btn bg-info" href="{{ route('hotspot.voucher.print', ['id' => $printComment, 'small' => 'yes']) }}" target="_blank"><i class="fa fa-print"></i> Print Small</a>
                        @endif
                    </div>

                    <table class="table">
                        <tr>
                            <td class="align-middle" style="width:220px;">Qty</td>
                            <td><input class="form-control" type="number" name="qty" min="1" max="500" value="{{ old('qty', 10) }}" required></td>
                        </tr>
                        <tr>
                            <td class="align-middle">Server</td>
                            <td>
                                <select class="form-control" name="server" required>
                                    <option value="all">all</option>
                                    @foreach(($servers ?? []) as $s)
                                        @php $serverName = $s['name'] ?? $s['server'] ?? ''; @endphp
                                        @if($serverName !== '')
                                            <option value="{{ $serverName }}" {{ old('server') === $serverName ? 'selected' : '' }}>{{ $serverName }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Mode User</td>
                            <td>
                                <select class="form-control" id="userMode" name="user" required>
                                    <option value="up" {{ old('user') === 'up' ? 'selected' : '' }}>up (username & password beda)</option>
                                    <option value="vc" {{ old('user', 'vc') === 'vc' ? 'selected' : '' }}>vc (username = password)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Panjang User</td>
                            <td>
                                <select class="form-control" name="userl" required>
                                    @foreach([3,4,5,6,7,8] as $len)
                                        <option value="{{ $len }}" {{ (int) old('userl', 4) === $len ? 'selected' : '' }}>{{ $len }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Prefix</td>
                            <td><input class="form-control" type="text" maxlength="6" name="prefix" value="{{ old('prefix') }}"></td>
                        </tr>
                        <tr>
                            <td class="align-middle">Character</td>
                            <td>
                                <select class="form-control" name="char" required>
                                    <option value="lower" {{ old('char') === 'lower' ? 'selected' : '' }}>Random abcd</option>
                                    <option value="upper" {{ old('char') === 'upper' ? 'selected' : '' }}>Random ABCD</option>
                                    <option value="upplow" {{ old('char') === 'upplow' ? 'selected' : '' }}>Random aBcD</option>
                                    <option value="mix" {{ old('char', 'mix2') === 'mix' ? 'selected' : '' }}>Random 5ab2c34d</option>
                                    <option value="mix1" {{ old('char') === 'mix1' ? 'selected' : '' }}>Random 5AB2C34D</option>
                                    <option value="mix2" {{ old('char', 'mix2') === 'mix2' ? 'selected' : '' }}>Random 5aB2c34D</option>
                                    <option id="optNum" value="num" {{ old('char') === 'num' ? 'selected' : '' }}>Random 1234</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Profile</td>
                            <td>
                                <select class="form-control" name="profile" required>
                                    @foreach(($profiles ?? []) as $p)
                                        @php $name = $p['name'] ?? ''; @endphp
                                        @if($name !== '')
                                            <option value="{{ $name }}" {{ old('profile', request('profile')) === $name ? 'selected' : '' }}>{{ $name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Time Limit</td>
                            <td><input class="form-control" type="text" name="timelimit" value="{{ old('timelimit') }}" placeholder="mis. 1d / 8h"></td>
                        </tr>
                        <tr>
                            <td class="align-middle">Data Limit</td>
                            <td>
                                <div class="input-group">
                                    <input class="form-control" type="number" min="0" max="9999" name="datalimit" value="{{ old('datalimit') }}">
                                    <select class="form-control" name="mbgb" required>
                                        <option value="1048576" {{ old('mbgb')=='1048576' ? 'selected' : '' }}>MB</option>
                                        <option value="1073741824" {{ old('mbgb')=='1073741824' ? 'selected' : '' }}>GB</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="align-middle">Comment Tambahan</td>
                            <td><input class="form-control" type="text" name="adcomment" value="{{ old('adcomment') }}" placeholder="contoh: promo"></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-ticket"></i> Last Generate</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><td>Kode</td><td>{{ $last['code'] ?? '-' }}</td></tr>
                    <tr><td>Tanggal</td><td>{{ $last['date'] ?? '-' }}</td></tr>
                    <tr><td>Profile</td><td>{{ $last['profile'] ?? '-' }}</td></tr>
                    <tr><td>Mode User</td><td>{{ $last['user_mode'] ?? '-' }}</td></tr>
                    <tr><td>Time Limit</td><td>{{ $last['timelimit'] ?? '-' }}</td></tr>
                    <tr><td>Data Limit</td><td>{{ $last['datalimit'] ?? '-' }}</td></tr>
                    <tr><td>Qty</td><td>{{ $last['qty'] ?? '-' }}</td></tr>
                    <tr><td>Comment</td><td>{{ $last['comment'] ?? '-' }}</td></tr>
                </table>
                <p class="text-muted" style="font-size:12px;">
                    Format voucher mengikuti template lama: mode <code>vc</code>, comment batch <code>vc-xxx-mm.dd.yy-opsional</code>, dan tombol print langsung.
                </p>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var mode = document.getElementById('userMode');
    var optNum = document.getElementById('optNum');
    if (!mode || !optNum) return;

    function syncCharOptions() {
        // Template lama: opsi "num" dipakai untuk VC, bukan UP.
        if (mode.value === 'up') {
            if (optNum.selected) {
                mode.form.querySelector('select[name="char"]').value = 'mix2';
            }
            optNum.style.display = 'none';
        } else {
            optNum.style.display = 'block';
        }
    }

    mode.addEventListener('change', syncCharOptions);
    syncCharOptions();
})();
</script>
@endpush
@endsection
