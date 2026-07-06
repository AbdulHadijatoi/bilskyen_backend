@props([
    'items' => [],
    'title' => null,
])

<section class="py-16 bg-muted/50">
    <div class="container mx-auto px-4 md:px-6 max-w-3xl">
        <h2 class="text-3xl font-bold tracking-tight text-center mb-10">
            {{ $title ?? __('messages.dealer_marketing.pricing.faq_title') }}
        </h2>
        <div class="space-y-3" id="pricing-faq">
            @foreach($items as $index => $item)
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <button type="button"
                            class="faq-toggle flex w-full items-center justify-between px-5 py-4 text-left text-sm font-medium hover:bg-muted/50 transition-colors"
                            aria-expanded="false"
                            data-faq-index="{{ $index }}">
                        <span>{{ $item['question'] }}</span>
                        <svg class="faq-icon h-5 w-5 shrink-0 text-muted-foreground transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <div class="faq-panel hidden border-t border-border px-5 py-4 text-sm text-muted-foreground leading-relaxed">
                        {!! nl2br(e($item['answer'])) !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@once
    @push('scripts')
    <script>
    (function () {
        document.querySelectorAll('.faq-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const panel = btn.nextElementSibling;
                const icon = btn.querySelector('.faq-icon');
                const isOpen = !panel.classList.contains('hidden');
                document.querySelectorAll('.faq-panel').forEach(function (p) { p.classList.add('hidden'); });
                document.querySelectorAll('.faq-toggle').forEach(function (b) {
                    b.setAttribute('aria-expanded', 'false');
                    const i = b.querySelector('.faq-icon');
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
@endonce
