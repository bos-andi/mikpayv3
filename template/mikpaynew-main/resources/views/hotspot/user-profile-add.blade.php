@extends('layouts.dashboard')

@section('title', 'Add User Profile')
@section('heading', 'User Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3><i class="fa fa-plus"></i> Add User Profile</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <a href="{{ route('hotspot.user-profiles') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <form method="POST" action="{{ route('hotspot.user-profiles.store') }}" autocomplete="off">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <td style="width: 180px; vertical-align: middle;">Name</td>
                                <td>
                                    <input class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus
                                        oninput="this.value=this.value.replace(/\s+/g,'-');">
                                    @error('name')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Address Pool</td>
                                <td>
                                    <select class="form-control" name="ppool">
                                        <option value="none">none</option>
                                        @foreach(($pools ?? []) as $p)
                                            <option value="{{ $p['name'] ?? '' }}">{{ $p['name'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Shared Users</td>
                                <td>
                                    <input class="form-control" type="number" min="0" name="sharedusers" value="{{ old('sharedusers', 1) }}" required>
                                    @error('sharedusers')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Rate limit [up/down]</td>
                                <td>
                                    <input class="form-control" type="text" name="ratelimit" value="{{ old('ratelimit') }}"
                                        placeholder="Example : 512k/1M">
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Expired Mode</td>
                                <td>
                                    <select class="form-control" name="expmode" id="expmode" required
                                        onchange="(function(){var v=document.getElementById('expmode').value; document.getElementById('validityRow').style.display = (v==='0') ? 'none' : '';})()">
                                        <option value="0" {{ old('expmode')==='0' ? 'selected' : '' }}>None</option>
                                        <option value="rem" {{ old('expmode')==='rem' ? 'selected' : '' }}>Remove</option>
                                        <option value="ntf" {{ old('expmode')==='ntf' ? 'selected' : '' }}>Notice</option>
                                        <option value="remc" {{ old('expmode')==='remc' ? 'selected' : '' }}>Remove &amp; Record</option>
                                        <option value="ntfc" {{ old('expmode')==='ntfc' ? 'selected' : '' }}>Notice &amp; Record</option>
                                    </select>
                                    @error('expmode')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr id="validityRow" style="{{ old('expmode','0') === '0' ? 'display:none;' : '' }}">
                                <td style="vertical-align: middle;">Validity</td>
                                <td>
                                    <input class="form-control" type="text" name="validity" value="{{ old('validity') }}" placeholder="contoh: 1d / 6h">
                                    @error('validity')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr id="graceperiodRow" style="display:none;">
                                <td style="vertical-align: middle;">Grace Period</td>
                                <td>
                                    <input class="form-control" type="text" name="graceperiod" value="{{ old('graceperiod', '5m') }}" placeholder="5m">
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Price</td>
                                <td>
                                    <input class="form-control" type="number" min="0" name="price" value="{{ old('price') }}" placeholder="0">
                                    @error('price')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Selling Price</td>
                                <td>
                                    <input class="form-control" type="number" min="0" name="sprice" value="{{ old('sprice') }}" placeholder="0">
                                    @error('sprice')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Lock User</td>
                                <td>
                                    <select class="form-control" name="lockunlock" required>
                                        <option value="Disable" {{ old('lockunlock')==='Disable' ? 'selected' : '' }}>Disable</option>
                                        <option value="Enable" {{ old('lockunlock')==='Enable' ? 'selected' : '' }}>Enable</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td style="vertical-align: middle;">Parent Queue</td>
                                <td>
                                    <select class="form-control" name="parent">
                                        <option value="none">none</option>
                                        @foreach(($queues ?? []) as $q)
                                            <option value="{{ $q['name'] ?? '' }}">{{ $q['name'] ?? '' }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>

                @if(session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success mt-3">{{ session('success') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

