@extends('layouts.superadmin')

@section('title', 'Pembayaran User')
@section('heading', 'Inbox pembayaran')

@section('content')
<div class="card">
    <div class="card-header"><h4 style="margin:0;"><i class="fa fa-credit-card"></i> Inbox Pembayaran User</h4></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Paket</th>
                        <th>Channel</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td><code>{{ $p->payment_id }}</code><br><small>{{ optional($p->created_at)->format('d M Y H:i') }}</small></td>
                        <td>{{ $p->user->username ?? $p->user->name ?? '—' }}</td>
                        <td>{{ $packages[$p->package]['name'] ?? ucfirst($p->package) }}</td>
                        <td>{{ ['bank_transfer' => 'Transfer Bank', 'ewallet' => 'E-Wallet', 'qris' => 'QRIS'][$p->payment_channel] ?? ucfirst(str_replace('_', ' ', (string) $p->payment_channel)) ?: '—' }}</td>
                        <td>Rp {{ number_format((int)$p->amount, 0, ',', '.') }}</td>
                        <td>
                            @if($p->status === 'pending')
                                <span class="badge badge-warning">pending</span>
                            @elseif($p->status === 'approved')
                                <span class="badge badge-success">approved</span>
                            @else
                                <span class="badge badge-danger">rejected</span>
                            @endif
                        </td>
                        <td style="max-width:220px;">{{ $p->customer_note ?: '-' }}</td>
                        <td>
                            @if($p->proof_file)
                                <a href="{{ asset('storage/'.$p->proof_file) }}" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-image"></i> Lihat</a>
                            @else
                                -
                            @endif
                        </td>
                        <td style="min-width: 210px;">
                            @if($p->status === 'pending')
                                <form method="POST" action="{{ route('superadmin.payments.approve', $p) }}" style="display:inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Approve</button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.payments.reject', $p) }}" style="display:inline-block; margin-top:6px;">
                                    @csrf
                                    <input type="text" name="reject_reason" placeholder="Alasan tolak" class="form-control input-sm" style="width:140px; display:inline-block;">
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-times"></i> Reject</button>
                                </form>
                            @else
                                <small class="text-muted">Diproses</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted">Belum ada pembayaran user.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
