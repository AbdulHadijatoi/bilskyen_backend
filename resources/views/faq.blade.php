@extends('layouts.app')

@section('title', ($faqHeaderTitle ?? __('messages.pages.faq.header_title')) . ' | Bilskyen')

@if(!empty($faqSchema))
@push('head')
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@endif

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-16 text-center md:py-20" aria-labelledby="faq-heading">
        <div class="container mx-auto px-4 md:px-6">
            <h1 id="faq-heading" class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $faqHeaderTitle }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg leading-relaxed">
                {{ $faqHeaderDescription }}
            </p>
        </div>
    </section>

    <section class="py-12 md:py-16" aria-label="{{ __('messages.pages.faq.sections_label') }}">
        <div class="container mx-auto px-4 md:px-6 max-w-3xl">
            @if(empty($faqSections))
                <p class="text-muted-foreground text-center text-base">
                    {{ __('messages.pages.faq.empty_content') }}
                </p>
            @else
                <div class="space-y-10" id="faq-sections">
                    @foreach($faqSections as $section)
                        @if(empty($section['items']))
                            @continue
                        @endif
                        <div class="faq-section" id="faq-section-{{ $section['id'] }}">
                            @if($section['title'] !== '')
                                <h2 class="text-xl font-semibold tracking-tight mb-4">
                                    {{ $section['title'] }}
                                </h2>
                            @endif
                            <div class="space-y-3">
                                @foreach($section['items'] as $index => $item)
                                    <div class="rounded-lg border border-border bg-card overflow-hidden">
                                        <button type="button"
                                                class="faq-toggle flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-medium hover:bg-muted/50 transition-colors"
                                                aria-expanded="false"
                                                data-faq-id="{{ $section['id'] }}-{{ $index }}">
                                            <span>{{ $item['question'] }}</span>
                                            <svg class="faq-icon h-5 w-5 shrink-0 text-muted-foreground transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                        <div class="faq-panel hidden border-t border-border px-5 py-4 text-sm text-muted-foreground leading-relaxed">
                                            {!! nl2br(e($item['answer'])) !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-12 rounded-lg border border-border bg-muted/40 px-6 py-6 text-center">
                <p class="text-sm text-muted-foreground">
                    {{ __('messages.pages.faq.still_need_help') }}
                </p>
                <a href="{{ route('contact') }}" class="mt-3 inline-flex h-10 items-center justify-center rounded-lg bg-primary px-5 text-sm font-medium text-primary-foreground transition hover:opacity-90">
                    {{ __('messages.pages.faq.contact_support') }}
                </a>
            </div>
        </div>
    </section>
</div>

@if(!empty($faqChatbotEnabled))
    @include('components.faq-chatbot')
@endif
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('#faq-sections .faq-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = btn.nextElementSibling;
            var icon = btn.querySelector('.faq-icon');
            var isOpen = !panel.classList.contains('hidden');
            var root = btn.closest('.faq-section') || document;
            root.querySelectorAll('.faq-panel').forEach(function (p) { p.classList.add('hidden'); });
            root.querySelectorAll('.faq-toggle').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
                var i = b.querySelector('.faq-icon');
                if (i) i.style.transform = '';
            });
            if (!isOpen) {
                panel.classList.remove('hidden');
                btn.setAttribute('aria-expanded', 'true');
                if (icon) icon.style.transform = 'rotate(180deg)';
            }
        });
    });
})();
</script>
@endpush
