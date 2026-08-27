@extends('layouts.superadmin')

@section('title', 'Konten Landing — MikPay')
@section('heading', 'Konten Landing')

@section('content')
<form method="POST" action="{{ route('superadmin.landing.update') }}" class="space-y-5">
    @csrf

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Hero</h2>
            <p class="text-xs text-slate-500 mt-1">Teks utama di bagian atas landing page.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Badge</label>
                <input type="text" name="hero_badge" value="{{ old('hero_badge', $content['hero_badge'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Judul</label>
                <input type="text" name="hero_title" value="{{ old('hero_title', $content['hero_title'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Highlight judul</label>
                <input type="text" name="hero_title_highlight" value="{{ old('hero_title_highlight', $content['hero_title_highlight'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Subjudul</label>
                <textarea name="hero_subtitle" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">{{ old('hero_subtitle', $content['hero_subtitle'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">CTA utama</label>
                <input type="text" name="hero_cta_primary" value="{{ old('hero_cta_primary', $content['hero_cta_primary'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">CTA sekunder</label>
                <input type="text" name="hero_cta_secondary" value="{{ old('hero_cta_secondary', $content['hero_cta_secondary'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">CTA penutup</h2>
            <p class="text-xs text-slate-500 mt-1">Blok ajakan daftar di bagian bawah halaman.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Judul</label>
                <input type="text" name="cta_title" value="{{ old('cta_title', $content['cta_title'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Subjudul</label>
                <textarea name="cta_subtitle" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">{{ old('cta_subtitle', $content['cta_subtitle'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">Teks tombol</label>
                <input type="text" name="cta_button" value="{{ old('cta_button', $content['cta_button'] ?? '') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">FAQ</h2>
            <p class="text-xs text-slate-500 mt-1">Setiap FAQ dipisah baris kosong. Baris pertama = pertanyaan, baris berikutnya = jawaban (HTML ringan diperbolehkan).</p>
        </div>
        <textarea name="faqs_text" rows="16" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-mono">{{ old('faqs_text', $faqsText) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold text-white gradient-bg px-5 py-2.5 rounded-xl shadow-md">
            <i class="fa-solid fa-floppy-disk"></i> Simpan konten
        </button>
        <a href="{{ route('home') }}" target="_blank" class="text-xs font-semibold text-slate-500 hover:text-indigo-600">Preview landing →</a>
    </div>
</form>
@endsection
