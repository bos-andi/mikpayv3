@extends('layouts.dashboard')

@section('title', 'Tambah User Hotspot')
@section('heading', 'Tambah User')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-user-plus"></i> Tambah User Hotspot Manual</h3>
            </div>
            <div class="card-body">
                <div class="row" style="gap:18px;">
                    <div class="col-lg-7">
                        <form method="POST" action="{{ route('hotspot.add-user.store') }}" autocomplete="off">
                            @csrf
                            <div class="mb-3">
                                <a href="{{ route('hotspot.users') }}" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Kembali ke Daftar User</a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <td class="align-middle" style="width:180px;">Server</td>
                                        <td>
                                            <select class="form-control" name="server">
                                                <option value="all">all</option>
                                                @foreach(($servers ?? []) as $s)
                                                    <option value="{{ $s['name'] ?? $s['server'] ?? ($s['.id'] ?? '') }}">
                                                        {{ $s['name'] ?? $s['server'] ?? ($s['.id'] ?? '-') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Name</td>
                                        <td><input class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus></td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Password</td>
                                        <td>
                                            <div class="input-group">
                                                <input id="passUser" class="form-control" type="password" name="pass" value="" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="(function(){var x=document.getElementById('passUser'); x.type = (x.type==='password') ? 'text' : 'password';})();">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Profile</td>
                                        <td>
                                            <select class="form-control" name="profile" required>
                                                @foreach(($profiles ?? []) as $p)
                                                    <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Time Limit (mis. 8h / 1d)</td>
                                        <td><input class="form-control" type="text" name="timelimit" value="{{ old('timelimit') }}" placeholder="(kosongkan jika tidak mau overwrite)"></td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Data Limit</td>
                                        <td>
                                            <div class="input-group">
                                                <input class="form-control" type="number" min="0" max="9999" name="datalimit" value="{{ old('datalimit') }}" placeholder="0">
                                                <select class="form-control" name="mbgb">
                                                    <option value="1048576" {{ old('mbgb')=='1048576' ? 'selected' : '' }}>MB</option>
                                                    <option value="1073741824" {{ old('mbgb')=='1073741824' ? 'selected' : '' }}>GB</option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="align-middle">Comment (kode)</td>
                                        <td>
                                            <input class="form-control" type="text" name="comment" value="{{ old('comment') }}" placeholder="contoh: 4000">
                                            <div class="text-muted" style="font-size:12px;margin-top:6px;">
                                                Format comment voucher mengikuti template: jika <code>name==pass</code> jadi <code>vc-</code>, selain itu <code>up-</code>.
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                            </div>

                            @if(session('error'))
                                <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                            @endif
                            @if(session('success'))
                                <div class="alert alert-success mt-3">{{ session('success') }}</div>
                            @endif
                            @error('name')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                        </form>
                    </div>

                    <div class="col-lg-5">
                        <div class="card box-bordered" style="background:#11182733;border:1px solid rgba(255,255,255,.08);">
                            <div class="card-header">
                                <h3><i class="fa fa-book"></i> Panduan Cepat</h3>
                            </div>
                            <div class="card-body">
                                <div style="font-size:13px;color:rgba(17,24,39,.8);">
                                    <div class="mb-2"><b>Durasi (limit-uptime)</b> akan overwrite jika kolom <code>Time Limit</code> diisi.</div>
                                    <div class="mb-2"><b>Data limit</b> akan overwrite jika <code>datalimit</code> & unit diisi &gt; 0.</div>
                                    <div class="mb-2">Untuk voucher <b>1 hari murni</b>, isi <code>comment</code> dan <b>kosongkan</b> <code>Time Limit</code>.</div>
                                    <div class="mt-3 text-muted">
                                        Setelah simpan, sistem redirect ke <b>Daftar User</b> sesuai profile & comment.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
