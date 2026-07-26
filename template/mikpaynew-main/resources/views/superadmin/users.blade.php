@extends('layouts.superadmin')

@section('title', 'Pengguna')
@section('heading', 'Pengguna & trial')

@section('content')
@php
    use App\Support\SubscriptionGate;
    use Carbon\Carbon;

    $packageLabel = static function (?string $pkg, ?string $status): string {
        $pkg = $pkg ?? '';
        $status = strtolower((string) ($status ?? ''));
        return match (true) {
            $status === 'pending_payment' => 'Menunggu pembayaran',
            in_array($pkg, ['trial', 'trial_5_days'], true) => 'Trial 5 hari',
            $pkg === 'monthly' => 'Paket 1 bulan',
            $pkg === 'monthly5' => 'Paket 5 bulan',
            $pkg === '' || $pkg === null => '—',
            default => $pkg,
        };
    };

    $subscriptionDaysLeft = static function ($user): string {
        if (empty($user->subscription_end)) {
            return '—';
        }
        try {
            $endDay = Carbon::parse($user->subscription_end)->startOfDay();
            $today = Carbon::today();
            $d = (int) $today->diffInDays($endDay, false);
            if ($d < 0) {
                return 'Sudah lewat';
            }
            if ($d === 0) {
                return 'Berakhir hari ini';
            }

            return $d.' hari';
        } catch (\Throwable $e) {
            return '—';
        }
    };

    $appAccessLabel = static function ($user): array {
        if (($user->status ?? '') !== 'active') {
            return ['Akun nonaktif', 'default'];
        }
        if (SubscriptionGate::allowsAppAccess($user)) {
            return ['Bisa akses app', 'success'];
        }

        return ['Terkunci (langganan)', 'danger'];
    };
@endphp
<style>
    .sa-user-actions { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; min-width: 200px; }
    .sa-user-actions form { margin: 0; }
    .sa-users-table { font-size: 12px; }
    .sa-users-table th { white-space: nowrap; vertical-align: middle; }
    .sa-users-table td { vertical-align: top; }
    .sa-cell-muted { color: #64748b; font-size: 11px; max-width: 220px; }
    .sa-cell-truncate {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
        vertical-align: bottom;
    }
    .sa-badge-role { font-size: 10px; text-transform: uppercase; }
    .sa-router-list {
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 11px;
        max-width: 220px;
        max-height: 140px;
        overflow-y: auto;
    }
    .sa-router-list li {
        margin: 0 0 5px;
        padding: 5px 7px;
        background: #f8fafc;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        line-height: 1.35;
    }
    .sa-router-list li:last-child { margin-bottom: 0; }
    .sa-router-list .sa-r-name { font-weight: 700; color: #0f172a; }
    .sa-router-list .sa-r-meta { color: #64748b; font-size: 10px; }
</style>
<div class="card">
    <div class="card-header">
        <h4 style="margin:0;"><i class="fa fa-users"></i> Data pengguna &amp; trial (5 hari)</h4>
        <p class="text-muted" style="margin:8px 0 0;font-size:12px;">
            Kolom mengikuti isian registrasi (nama, email, bisnis, WA, alamat), status langganan, dan <strong>router MikroTik</strong> yang terdaftar per akun tenant.
            Trial dari daftar atau dari tombol di bawah sama-sama memakai status <code>trial</code> + tanggal berakhir; akses app dicek lewat tanggal berakhir (inklusif sampai akhir hari).
        </p>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table table-hover table-bordered sa-users-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Bisnis</th>
                        <th>Alamat</th>
                        <th>WhatsApp</th>
                        <th>Role</th>
                        <th>Status akun</th>
                        <th>Paket</th>
                        <th>Status langganan</th>
                        <th>Mulai</th>
                        <th>Berakhir</th>
                        <th>Sisa (kalender)</th>
                        <th>Akses app</th>
                        <th>Router MikroTik</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    @php
                        [$accessText, $accessClass] = $appAccessLabel($u);
                    @endphp
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td>
                            <strong>{{ $u->username ?? '—' }}</strong>
                        </td>
                        <td>{{ $u->name ?? '—' }}</td>
                        <td>
                            @if($u->email)
                                <a href="mailto:{{ $u->email }}">{{ $u->email }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="sa-cell-truncate" title="{{ $u->business_name }}">{{ $u->business_name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="sa-cell-muted sa-cell-truncate" style="max-width:240px;white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;" title="{{ $u->business_address }}">{{ $u->business_address ?? '—' }}</span>
                        </td>
                        <td>
                            @if($u->whatsapp)
                                <span dir="ltr">{{ $u->whatsapp }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td><span class="label label-default sa-badge-role">{{ $u->role ?? '—' }}</span></td>
                        <td>
                            @if(($u->status ?? '') === 'active')
                                <span class="label label-success">active</span>
                            @else
                                <span class="label label-default">{{ $u->status ?? '—' }}</span>
                            @endif
                        </td>
                        <td>{{ $packageLabel($u->subscription_package, $u->subscription_status) }}</td>
                        <td>
                            @php $st = strtolower((string) ($u->subscription_status ?? '')); @endphp
                            @if($st === 'trial')
                                <span class="label label-info">trial</span>
                            @elseif($st === 'active')
                                <span class="label label-success">active</span>
                            @elseif($st === 'expired')
                                <span class="label label-warning">expired</span>
                            @elseif($st === 'pending_payment')
                                <span class="label label-warning">pending_payment</span>
                            @elseif($st === '')
                                <span class="text-muted">—</span>
                            @else
                                <span class="label label-default">{{ $u->subscription_status }}</span>
                            @endif
                        </td>
                        <td>{{ $u->subscription_start ? \Carbon\Carbon::parse($u->subscription_start)->format('d M Y') : '—' }}</td>
                        <td>{{ $u->subscription_end ? \Carbon\Carbon::parse($u->subscription_end)->format('d M Y') : '—' }}</td>
                        <td>{{ $subscriptionDaysLeft($u) }}</td>
                        <td><span class="label label-{{ $accessClass }}">{{ $accessText }}</span></td>
                        <td>
                            @php $routers = $u->mikpayRouters ?? collect(); @endphp
                            @if($routers->isEmpty())
                                <span class="text-muted">—</span>
                            @else
                                <ul class="sa-router-list">
                                    @foreach($routers as $router)
                                        <li>
                                            <span class="sa-r-name">{{ $router->router_name ?: $router->session_name }}</span>
                                            <span class="sa-r-meta"><br>
                                                <code style="font-size:10px;background:#fff;padding:1px 4px;border-radius:3px;">{{ $router->session_name }}</code>
                                                @if(($router->status ?? '') === 'enable')
                                                    <span class="label label-success" style="font-size:9px;padding:1px 5px;margin-left:4px;">enable</span>
                                                @else
                                                    <span class="label label-default" style="font-size:9px;padding:1px 5px;margin-left:4px;">{{ $router->status ?? '—' }}</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>{{ $u->created_at ? $u->created_at->format('d M Y H:i') : '—' }}</td>
                        <td>
                            <div class="sa-user-actions">
                                <form method="POST" action="{{ route('superadmin.users.trial', $u) }}" onsubmit="return confirm('Set trial 5 hari untuk {{ $u->username ?? $u->name }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-primary"><i class="fa fa-clock-o"></i> Set Trial 5 Hari</button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.users.expire-subscription', $u) }}" onsubmit="return confirm('Tandai langganan {{ $u->username ?? $u->name }} sebagai habis (expired)? User hanya bisa ke halaman Langganan sampai bayar.');">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-warning"><i class="fa fa-hourglass-end"></i> Expire langganan</button>
                                </form>
                                @if(($u->status ?? '') === 'active')
                                <form method="POST" action="{{ route('superadmin.users.deactivate', $u) }}" onsubmit="return confirm('Nonaktifkan akun {{ $u->username ?? $u->name }}? User tidak bisa login.');">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-ban"></i> Nonaktifkan akun</button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('superadmin.users.activate', $u) }}" onsubmit="return confirm('Aktifkan kembali akun {{ $u->username ?? $u->name }}?');">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Aktifkan akun</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="18" class="text-center text-muted">Tidak ada user.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
