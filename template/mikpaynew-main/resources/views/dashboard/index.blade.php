@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('mikpay/dashboard-custom.css') }}?v={{ time() }}">

<div id="reloadHome" class="dashboard-container-modern">
    <!-- Dashboard Header (timezone | time | date | uptime) -->
    <div class="dashboard-header-modern">
        <div class="brand-section">
            <div class="brand-text">MIKPAY</div>
        </div>
        <div class="time-section">
            <span id="timezone-display">{{ $clock['time-zone-name'] ?? config('app.timezone') }}</span> |
            <span id="time-display">{{ $clock['time'] ?? date('H:i:s') }}</span> |
            <span id="date-display">{{ isset($clock['date']) ? ucfirst($clock['date']) : date('d M Y') }}</span> |
            <span id="uptime-display"><i class="fa fa-clock-o"></i> {{ $uptimeFormatted }}</span>
        </div>
    </div>

    <!-- HOTSPOT -->
    <div class="stats-section-label">
        <i class="fa fa-wifi"></i> HOTSPOT
    </div>
    <div class="dashboard-cards-modern">
        <div class="dashboard-card-modern card-red">
            <div class="dashboard-card-number" id="hotspot-active-count">{{ $countHotspotActive }}</div>
            <div class="dashboard-card-label"><i class="fa fa-wifi"></i> Active Hotspot</div>
            <div class="dashboard-card-icon"><i class="fa fa-wifi"></i></div>
        </div>
        <div class="dashboard-card-modern card-yellow">
            <div class="dashboard-card-number" id="hotspot-users-count">{{ $countHotspotUsers }}</div>
            <div class="dashboard-card-label"><i class="fa fa-users"></i> Total Users</div>
            <div class="dashboard-card-icon"><i class="fa fa-users"></i></div>
        </div>
        <div class="dashboard-card-modern card-green">
            <div class="dashboard-card-number" id="income-display" style="font-size: 24px;">
                {{ $currency }} {{ number_format($hotspotIncomeMonth, 0, ',', '.') }}
            </div>
            <div class="dashboard-card-label"><i class="fa fa-money"></i> Hotspot Income</div>
            <div class="dashboard-card-sub"><small>Bulan ini</small></div>
            <div class="dashboard-card-icon"><i class="fa fa-money"></i></div>
        </div>
    </div>

    <!-- PPP / PPPOE -->
    <div class="stats-section-label" style="margin-top: 15px;">
        <i class="fa fa-plug"></i> PPP / PPPOE
    </div>
    <div class="dashboard-cards-modern">
        <div class="dashboard-card-modern card-ppp-active">
            <div class="dashboard-card-number">{{ $countPppActive }}</div>
            <div class="dashboard-card-label"><i class="fa fa-plug"></i> Active PPP</div>
            <div class="dashboard-card-icon"><i class="fa fa-plug"></i></div>
        </div>
        <div class="dashboard-card-modern card-ppp-secrets">
            <div class="dashboard-card-number">{{ $countPppSecrets }}</div>
            <div class="dashboard-card-label"><i class="fa fa-key"></i> Total Secrets</div>
            <div class="dashboard-card-icon"><i class="fa fa-key"></i></div>
        </div>
        <div class="dashboard-card-modern card-ppp-income">
            <div class="dashboard-card-number" style="font-size: 24px;">
                {{ $currency }} {{ number_format($pppIncomeMonth, 0, ',', '.') }}
            </div>
            <div class="dashboard-card-label"><i class="fa fa-money"></i> PPP Income</div>
            <div class="dashboard-card-sub"><small>Bulan ini</small></div>
            <div class="dashboard-card-icon"><i class="fa fa-money"></i></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row" style="margin-top: 25px; margin-bottom: 20px;">
        <div class="col-12">
            <div class="dashboard-panel-modern quick-actions-panel">
                <div class="dashboard-panel-header" style="background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);">
                    <i class="fa fa-bolt"></i> Quick Actions
                </div>
                <div class="dashboard-panel-body" style="padding: 20px;">
                    <div class="quick-actions-grid quick-actions-fullwidth">
                        <a href="#" class="quick-action-btn qa-generate">
                            <div class="qa-icon"><i class="fa fa-magic"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Generate User</span>
                                <span class="qa-desc">Create hotspot vouchers</span>
                            </div>
                        </a>
                        <a href="#" class="quick-action-btn qa-adduser">
                            <div class="qa-icon"><i class="fa fa-user-plus"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Add User</span>
                                <span class="qa-desc">Manual hotspot user</span>
                            </div>
                        </a>
                        <a href="#" class="quick-action-btn qa-ppp">
                            <div class="qa-icon"><i class="fa fa-plug"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Add PPP Secret</span>
                                <span class="qa-desc">Create PPP account</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Router info + App Log -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-8">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-server"></i> {{ $identity }}
                    <span style="float:right; font-weight: 400; font-size: 12px;">
                        <i class="fa fa-clock-o"></i> {{ $uptimeFormatted }}
                    </span>
                </div>
                <div class="dashboard-panel-body" style="padding: 20px;">
                    <div class="resource-info-row">
                        <div class="resource-info-item">
                            <div class="resource-info-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                                <i class="fa fa-microchip"></i>
                            </div>
                            <div class="resource-info-text">
                                <span class="resource-info-value">{{ $resource['board-name'] ?? '-' }}</span>
                                <span class="resource-info-label">{{ $routerboard['model'] ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="resource-info-item">
                            <div class="resource-info-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                                <i class="fa fa-cog"></i>
                            </div>
                            <div class="resource-info-text">
                                <span class="resource-info-value">RouterOS {{ isset($resource['version']) ? explode(' ', $resource['version'])[0] : '-' }}</span>
                                <span class="resource-info-label">{{ $resource['architecture-name'] ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="resource-info-item">
                            <div class="resource-info-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <i class="fa fa-bolt"></i>
                            </div>
                            <div class="resource-info-text">
                                <span class="resource-info-value">{{ isset($syshealth['voltage']) ? $syshealth['voltage'].'V' : '-' }}</span>
                                <span class="resource-info-label">{{ isset($syshealth['temperature']) ? $syshealth['temperature'].'°C' : 'Health' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="resource-progress-section">
                        <div class="resource-progress-item">
                            <div class="resource-progress-header">
                                <span><i class="fa fa-tachometer"></i> CPU</span>
                                <span>{{ $cpuPercent }}%</span>
                            </div>
                            <div class="progress-modern">
                                <div class="progress-bar-modern {{ $cpuPercent > 80 ? 'progress-danger' : ($cpuPercent > 50 ? 'progress-warning' : 'progress-success') }}" style="width: {{ $cpuPercent }}%"></div>
                            </div>
                            <div class="resource-progress-detail">{{ $resource['cpu-count'] ?? 1 }}x {{ $resource['cpu-frequency'] ?? '-' }} MHz</div>
                        </div>
                        <div class="resource-progress-item">
                            <div class="resource-progress-header">
                                <span><i class="fa fa-database"></i> Memory</span>
                                <span>{{ $memoryPercent }}%</span>
                            </div>
                            <div class="progress-modern">
                                <div class="progress-bar-modern {{ $memoryPercent > 80 ? 'progress-danger' : ($memoryPercent > 50 ? 'progress-warning' : 'progress-success') }}" style="width: {{ $memoryPercent }}%"></div>
                            </div>
                            <div class="resource-progress-detail">{{ $memFreeFormatted }} free / {{ $memTotalFormatted }}</div>
                        </div>
                        <div class="resource-progress-item">
                            <div class="resource-progress-header">
                                <span><i class="fa fa-hdd-o"></i> Storage</span>
                                <span>{{ $hddPercent }}%</span>
                            </div>
                            <div class="progress-modern">
                                <div class="progress-bar-modern {{ $hddPercent > 80 ? 'progress-danger' : ($hddPercent > 50 ? 'progress-warning' : 'progress-success') }}" style="width: {{ $hddPercent }}%"></div>
                            </div>
                            <div class="resource-progress-detail">{{ $hddFreeFormatted }} free / {{ $hddTotalFormatted }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="dashboard-panel-modern">
                <div class="dashboard-panel-header">
                    <i class="fa fa-list"></i> App Log
                </div>
                <div class="dashboard-panel-body">
                    <div class="app-log-container">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="2" class="text-muted">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('sessions.disconnect') }}" style="display:inline; margin-top: 10px;">
    @csrf
    <button type="submit" class="btn btn-sm btn-secondary"><i class="fa fa-sign-out"></i> Ganti router</button>
</form>
@endsection
