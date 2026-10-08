@php
    $useOld = $row === null;
    $val = fn (string $key, $default = null) => $useOld ? old($key, $default) : $default;
@endphp
<div class="col-span-2 md:col-span-3">
    <label class="{{ $labelClass }}">Profile hotspot</label>
    <input type="text" name="profile" list="vsProfiles" required maxlength="128" class="{{ $inputClass }}" value="{{ $val('profile', $row?->profile) }}" placeholder="Pilih / ketik nama profile" autocomplete="off">
</div>
<div class="col-span-2 md:col-span-3">
    <label class="{{ $labelClass }}">Nama paket</label>
    <input type="text" name="label" required maxlength="60" class="{{ $inputClass }}" value="{{ $val('label', $row?->label) }}" placeholder="mis. 1 Hari">
</div>
<div class="col-span-1 md:col-span-2">
    <label class="{{ $labelClass }}">Harga (Rp)</label>
    <input type="number" name="price" required min="{{ $minPrice }}" step="500" class="{{ $inputClass }}" value="{{ $val('price', $row?->price) }}" placeholder="5000">
</div>
<div class="col-span-1 md:col-span-2">
    <label class="{{ $labelClass }}">Batas waktu</label>
    <input type="text" name="limit_uptime" maxlength="32" class="{{ $inputClass }}" value="{{ $val('limit_uptime', $row?->limit_uptime) }}" placeholder="mis. 1d / 3h">
</div>
<div class="col-span-1 md:col-span-1">
    <label class="{{ $labelClass }}">Kuota MB</label>
    <input type="number" name="limit_quota_mb" min="1" class="{{ $inputClass }}" value="{{ $val('limit_quota_mb', $row?->limit_bytes ? (int) round($row->limit_bytes / 1048576) : null) }}" placeholder="∞">
</div>
<div class="col-span-1 md:col-span-1">
    <label class="{{ $labelClass }}">Urutan</label>
    <input type="number" name="sort" min="0" max="999" class="{{ $inputClass }}" value="{{ $val('sort', $row?->sort ?? 0) }}">
</div>
<div class="col-span-2 md:col-span-4">
    <label class="{{ $labelClass }}">Keterangan singkat <span class="normal-case font-normal">(opsional)</span></label>
    <input type="text" name="description" maxlength="160" class="{{ $inputClass }}" value="{{ $val('description', $row?->description) }}" placeholder="mis. Bebas pakai 24 jam, 2 perangkat">
</div>
<label class="col-span-2 md:col-span-2 flex items-center gap-2 self-end pb-2.5 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
    <input type="checkbox" name="is_active" value="1" class="w-4 h-4 accent-indigo-600" @checked($val('is_active', $row?->is_active ?? true))>
    Tampilkan di toko
</label>
