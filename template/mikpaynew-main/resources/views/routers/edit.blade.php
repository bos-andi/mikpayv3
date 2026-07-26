@extends('layouts.app')

@section('title', 'Edit Router')
@section('heading', 'Edit Router')

@section('content')
<div class="card">
    <div class="card-header"><h3><i class="fa fa-gear"></i> Edit Router — {{ $router->session_name }}</h3></div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert bg-danger mr-5" style="margin-bottom: 15px;">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('routers.update', $router) }}" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="settings-section">
                <div class="settings-section-header"><i class="fa fa-bookmark"></i> Session</div>
                <div class="settings-section-body">
                    <div class="settings-row">
                        <span class="settings-label">Nama Session</span>
                        <div class="settings-input">
                            <input type="text" name="session_name" value="{{ old('session_name', $router->session_name) }}" required pattern="[a-zA-Z0-9_-]+"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header"><i class="fa fa-server"></i> MikroTik</div>
                <div class="settings-section-body">
                    <div class="settings-row">
                        <span class="settings-label">IP:Port</span>
                        <div class="settings-input">
                            <input type="text" name="host" value="{{ old('host', $router->host) }}" required placeholder="192.168.1.1:8728"/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Username</span>
                        <div class="settings-input">
                            <input type="text" name="username" value="{{ old('username', $router->username) }}" required/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Password</span>
                        <div class="settings-input">
                            <input type="password" name="password" placeholder="Kosongkan jika tidak diubah"/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header"><i class="fa fa-info-circle"></i> Data</div>
                <div class="settings-section-body">
                    <div class="settings-row">
                        <span class="settings-label">Nama Router</span>
                        <div class="settings-input">
                            <input type="text" name="router_name" value="{{ old('router_name', $router->router_name) }}"/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Domain</span>
                        <div class="settings-input">
                            <input type="text" name="domain" value="{{ old('domain', $router->domain) }}"/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Mata Uang</span>
                        <div class="settings-input">
                            <input type="text" name="currency" value="{{ old('currency', $router->currency) }}" maxlength="4"/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Expiry (hari)</span>
                        <div class="settings-input">
                            <input type="number" name="expiry_days" value="{{ old('expiry_days', $router->expiry_days) }}" min="1" max="365"/>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Status</span>
                        <div class="settings-input">
                            <select name="status">
                                <option value="enable" {{ old('status', $router->status) === 'enable' ? 'selected' : '' }}>Enable</option>
                                <option value="disable" {{ old('status', $router->status) === 'disable' ? 'selected' : '' }}>Disable</option>
                            </select>
                        </div>
                    </div>
                    <div class="settings-row">
                        <span class="settings-label">Live Report</span>
                        <div class="settings-input">
                            <select name="livereport">
                                <option value="enable" {{ old('livereport', $router->livereport) === 'enable' ? 'selected' : '' }}>Enable</option>
                                <option value="disable" {{ old('livereport', $router->livereport) === 'disable' ? 'selected' : '' }}>Disable</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-actions">
                <button type="submit" class="btn btn-save-settings"><i class="fa fa-save"></i> Simpan</button>
                <a href="{{ route('sessions') }}" class="btn btn-reload"><i class="fa fa-times"></i> Batal</a>
            </div>
        </form>
    </div>
</div>

<style>
.settings-section { background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
.settings-section-header { background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%); color: #fff; padding: 15px 20px; font-size: 15px; font-weight: 600; }
.settings-section-body { padding: 20px; }
.settings-row { display: flex; align-items: center; margin-bottom: 15px; gap: 15px; }
.settings-row:last-child { margin-bottom: 0; }
.settings-label { min-width: 140px; font-size: 13px; font-weight: 500; }
.settings-input { flex: 1; }
.settings-input input, .settings-input select { width: 100%; padding: 10px 14px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 13px; }
.settings-actions { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0; }
.btn-save-settings { background: linear-gradient(135deg, #4D44B5 0%, #3d3690 100%); color: #fff; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
.btn-reload { background: #f8f9fa; color: #374151; padding: 10px 20px; border-radius: 8px; border: 1px solid #e0e0e0; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
</style>
@endsection
