@extends('layouts.dashboard')

@php $isEdit = !empty($customer_name) || $customer; @endphp
@section('title', $isEdit ? 'Atur Billing Pelanggan' : 'Tambah Pelanggan')
@section('heading', $isEdit ? 'Atur Billing Pelanggan' : 'Tambah Pelanggan')

@section('content')
<div class="card">
    <div class="card-header">
        <h3><i class="fa fa-user-plus"></i> {{ $isEdit ? 'Atur Billing Pelanggan' : 'Tambah Pelanggan' }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('ppp.billing.customers.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Customer (nama PPP / username) <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $customer_name ?? $customer?->customer_name ?? '') }}" {{ $isEdit ? 'readonly' : '' }} required>
                        @error('customer_name')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Tanggal Jatuh Tempo (1-31) <span class="text-danger">*</span></label>
                        <input type="number" name="due_day" class="form-control" min="0" max="31" value="{{ old('due_day', $customer?->due_day ?? 10) }}" required>
                        @error('due_day')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Tarif Bulanan (Rp)</label>
                        <input type="number" name="monthly_fee" class="form-control" min="0" value="{{ old('monthly_fee', $customer?->monthly_fee ?? 0) }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nama Tampilan</label>
                        <input type="text" name="display_name" class="form-control" value="{{ old('display_name', $customer?->display_name ?? '') }}" placeholder="Nama untuk invoice/WA">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer?->phone ?? '') }}" placeholder="Untuk reminder WhatsApp">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $customer?->email ?? '') }}" placeholder="email@contoh.com" autocomplete="off">
                        @error('email')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Zona / wilayah</label>
                        <input type="text" name="zone" class="form-control" value="{{ old('zone', $customer?->zone ?? '') }}" placeholder="RT/RW, cluster...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Nama paket layanan</label>
                        <input type="text" name="package_label" class="form-control" value="{{ old('package_label', $customer?->package_label ?? '') }}" placeholder="mis. 10 Mbps">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mulai berlangganan</label>
                        <input type="date" name="started_at" class="form-control" value="{{ old('started_at', $customer?->started_at?->format('Y-m-d') ?? '') }}">
                        @error('started_at')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes', $customer?->notes ?? '') }}">
                    </div>
                </div>
            </div>
            @if(!empty($promisePeriod) && !empty($customer_name))
            <div class="card border-info mb-3" style="border-radius:12px;">
                <div class="card-body">
                    <h5 class="card-title" style="font-size:1rem;"><i class="fa fa-calendar-check-o text-info"></i> Janji bayar — {{ $promisePeriodLabel ?? $promisePeriod }}</h5>
                    <p class="text-muted small mb-3">Hanya untuk tagihan periode ini. Bulan berikutnya kembali mengikuti tanggal jatuh tempo biasa kecuali Anda set janji lagi.</p>
                    <form method="POST" action="{{ route('ppp.billing.period-promise.store') }}" class="row g-3 align-items-end">
                        @csrf
                        <input type="hidden" name="customer_name" value="{{ $customer_name }}">
                        <input type="hidden" name="period" value="{{ $promisePeriod }}">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal janji bayar</label>
                            <input type="date" name="promised_pay_date" class="form-control" value="{{ old('promised_pay_date', $periodPromise?->promised_pay_date?->format('Y-m-d') ?? '') }}">
                            @error('promised_pay_date')<span class="text-danger small">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Catatan (opsional)</label>
                            <input type="text" name="promised_pay_note" class="form-control" maxlength="255" value="{{ old('promised_pay_note', $periodPromise?->promised_pay_note ?? '') }}" placeholder="mis. transfer sore">
                        </div>
                        <div class="col-md-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan janji</button>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="clear_promise" value="1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus janji bayar untuk periode {{ $promisePeriod }}?');"><i class="fa fa-times"></i> Hapus janji periode ini</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label">Alamat singkat</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Alamat pemasangan / lokasi...">{{ old('address', $customer?->address ?? '') }}</textarea>
                        @error('address')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Simpan</button>
            <a href="{{ route('ppp.billing', ['view' => 'customers']) }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Batal</a>
        </form>
    </div>
</div>
@endsection
