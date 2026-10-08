@php
    $faqs = $landing['faqs'] ?? [];
    $wa = config('mikpay.whatsapp');
    $waUrl = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['consult_message']);
@endphp

<section id="faq" class="py-20 lg:py-24 bg-white scroll-mt-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-12 gap-10 lg:gap-14">
            <div class="reveal lg:col-span-4">
                <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-indigo-600 bg-indigo-50 border border-indigo-100 px-3 py-1 rounded-full">FAQ</span>
                <h2 class="mt-4 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Pertanyaan yang sering diajukan.</h2>
                <p class="mt-4 text-slate-600">Belum menemukan jawaban? Tim kami siap membantu lewat WhatsApp.</p>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-white bg-emerald-500 hover:bg-emerald-600 px-5 py-3 rounded-2xl shadow-lg shadow-emerald-500/25 transition">
                    <i class="fa-brands fa-whatsapp text-base"></i> Tanya via WhatsApp
                </a>
            </div>

            <div class="lg:col-span-8 space-y-3">
                @foreach ($faqs as $i => $faq)
                    @php $faqId = $faq['id'] ?? ('faq'.($i + 1)); @endphp
                    <div class="reveal rounded-2xl border border-slate-200 bg-white hover:border-indigo-200 transition">
                        <button type="button" onclick="toggleFaq('{{ $faqId }}')" class="w-full flex justify-between items-center gap-4 text-left px-5 sm:px-6 py-5">
                            <span class="font-bold text-slate-900">{{ $faq['q'] ?? '' }}</span>
                            <span data-faq-icon="{{ $faqId }}" class="w-8 h-8 shrink-0 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 {{ $i === 0 ? 'rotate-45' : '' }}">
                                <i class="fa-solid fa-plus text-xs"></i>
                            </span>
                        </button>
                        <div id="{{ $faqId }}" class="{{ $i === 0 ? '' : 'hidden' }} px-5 sm:px-6 pb-5 -mt-1 text-sm text-slate-600 leading-relaxed">
                            {!! $faq['a'] ?? '' !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
