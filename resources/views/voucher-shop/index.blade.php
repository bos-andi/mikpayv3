@extends('voucher-shop.layout')

@section('content')
@php
    $rp = fn ($v) => 'Rp'.number_format((int) $v, 0, ',', '.');
    $duration = function (?string $v): string {
        if (! $v) {
            return '';
        }
        $units = ['w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'm' => 'menit', 's' => 'detik'];
        preg_match_all('/(\d+)([wdhms])/', $v, $m, PREG_SET_ORDER);

        return collect($m)->map(fn ($p) => $p[1].' '.$units[$p[2]])->implode(' ');
    };
    $quota = function (?int $b): string {
        if (! $b) {
            return '';
        }
        $mb = $b / 1048576;

        return $mb >= 1024 ? rtrim(rtrim(number_format($mb / 1024, 1, ',', '.'), '0'), ',').' GB' : number_format($mb, 0, ',', '.').' MB';
    };
    $fees = $items->mapWithKeys(fn ($i) => [$i->id => \App\Services\VoucherShopService::feeFor($i->price)]);
    $selected = (int) old('item_id', $items->first()?->id);
@endphp

@if (session('error'))
    <div class="vs-alert err" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="vs-alert err" role="alert">{{ $errors->first() }}</div>
@endif

@if (! $availability['open'])
    <div class="vs-card">
        <div class="vs-empty">
            <div class="ic" aria-hidden="true">🛠️</div>
            <h3>Toko sedang tutup</h3>
            <p>{{ $availability['reason'] }}</p>
        </div>
    </div>
@elseif ($items->isEmpty())
    <div class="vs-card">
        <div class="vs-empty">
            <div class="ic" aria-hidden="true">🎫</div>
            <h3>Belum ada paket</h3>
            <p>Paket voucher belum tersedia. Silakan cek lagi nanti.</p>
        </div>
    </div>
@else
    <form method="POST" action="{{ route('tenant.voucher-shop.store', ['tenant' => $shopTenant->subdomain]) }}" id="vsForm" class="vs-card">
        @csrf
        <div class="vs-steps" aria-hidden="true">
            <div><b>1. Pilih</b>paket</div>
            <div><b>2. Bayar</b>scan QRIS</div>
            <div><b>3. Online</b>pakai kode</div>
        </div>

        <h2><span class="vs-step">1</span> Pilih paket voucher</h2>
        <div class="vs-items">
            @foreach ($items as $item)
                @php
                    $bits = array_filter([$duration($item->limit_uptime), $quota($item->limit_bytes)]);
                @endphp
                <label class="vs-item">
                    <input type="radio" name="item_id" value="{{ $item->id }}" data-price="{{ $item->price }}" data-fee="{{ $fees[$item->id] }}" data-label="{{ $item->label }}" @checked($selected === $item->id) required>
                    <div class="vs-item-box">
                        <div class="vs-item-label">{{ $item->label }}</div>
                        <div class="vs-item-desc">{{ $item->description ?: implode(' · ', $bits) }}</div>
                        <div class="vs-item-price">{{ $rp($item->price) }}</div>
                        @if ($item->description && $bits)
                            <span class="vs-chip">{{ implode(' · ', $bits) }}</span>
                        @endif
                    </div>
                    <span class="vs-check" aria-hidden="true"></span>
                </label>
            @endforeach
        </div>

        <h2 style="margin-top:18px"><span class="vs-step">2</span> Kirim kode ke WhatsApp <span style="font-weight:500;color:var(--muted);font-size:12px">(opsional)</span></h2>
        <div class="vs-field">
            <input class="vs-input" type="tel" name="phone" inputmode="numeric" autocomplete="tel" maxlength="16" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}">
            <small>Kode voucher selalu tampil di layar setelah bayar. Isi nomor bila ingin salinannya dikirim lewat WhatsApp.</small>
        </div>

        <div class="vs-summary" aria-live="polite">
            <div class="vs-row"><span>Paket</span><b id="vsSumLabel">—</b></div>
            <div class="vs-row"><span>Harga voucher</span><b id="vsSumPrice">—</b></div>
            <div class="vs-row"><span>Biaya layanan</span><b id="vsSumFee">—</b></div>
            <div class="vs-row total"><span>Total bayar</span><b id="vsSumTotal">—</b></div>
        </div>

        <div class="vs-btns">
            <button type="submit" class="vs-btn vs-btn-primary" id="vsSubmit">
                <svg class="vs-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3V3Zm2 2v4h4V5H5Zm8-2h8v8h-8V3Zm2 2v4h4V5h-4ZM3 13h8v8H3v-8Zm2 2v4h4v-4H5Zm11-2h2v2h-2v-2Zm-3 0h2v3h-2v-3Zm6 0h2v4h-2v-4Zm-6 5h3v3h-3v-3Zm5 0h1v1h2v2h-3v-3Z"/></svg>
                Bayar dengan QRIS
            </button>
        </div>
        <p style="text-align:center;font-size:11.5px;color:var(--muted);margin:10px 0 0">
            Bisa dibayar pakai GoPay, OVO, DANA, ShopeePay, LinkAja &amp; m-banking.
        </p>
    </form>

    <div class="vs-alert info">
        <span aria-hidden="true">💡</span>
        <span>Belum terhubung internet? Buka halaman ini lewat jaringan <b>{{ $settings['title'] }}</b>, lalu bayar memakai aplikasi e-wallet di HP (pakai paket data) atau scan dari HP lain.</span>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('vsForm');
    if (!form) return;
    var fmt = function (n) { return 'Rp' + Number(n).toLocaleString('id-ID'); };
    function update() {
        var r = form.querySelector('input[name="item_id"]:checked');
        if (!r) return;
        var price = +r.dataset.price, fee = +r.dataset.fee;
        document.getElementById('vsSumLabel').textContent = r.dataset.label;
        document.getElementById('vsSumPrice').textContent = fmt(price);
        document.getElementById('vsSumFee').textContent = fmt(fee);
        document.getElementById('vsSumTotal').textContent = fmt(price + fee);
    }
    form.addEventListener('change', update);
    form.addEventListener('submit', function () {
        var b = document.getElementById('vsSubmit');
        b.disabled = true;
        b.textContent = 'Membuat QRIS…';
    });
    update();
})();
</script>
@endpush
