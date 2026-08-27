@php
    $faqs = $landing['faqs'] ?? [];
@endphp

<section id="faq" class="py-20 bg-white border-t border-slate-200">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600 mb-2">Pertanyaan Umum</h2>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Sering Ditanyakan Pengguna Baru</h3>
        </div>

        <div class="space-y-4">
            @foreach ($faqs as $i => $faq)
                @php $faqId = $faq['id'] ?? ('faq'.($i + 1)); @endphp
                <div class="p-5 rounded-2xl bg-white border border-slate-200/90 shadow-sm space-y-2">
                    <h4 class="font-bold text-sm text-slate-900 flex justify-between items-center cursor-pointer gap-4" onclick="toggleFaq('{{ $faqId }}')">
                        <span>{{ $faq['q'] ?? '' }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-slate-400 shrink-0"></i>
                    </h4>
                    <p id="{{ $faqId }}" class="text-xs text-slate-600 leading-relaxed pt-2 border-t border-slate-100">
                        {!! $faq['a'] ?? '' !!}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</section>
