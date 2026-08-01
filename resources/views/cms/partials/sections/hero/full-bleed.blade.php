@php $bg = !empty($content['image_url']) ? "background-image:linear-gradient(rgba(15,23,42,.55),rgba(15,23,42,.55)),url('".e($content['image_url'])."');background-size:cover;background-position:center;" : 'background: var(--cms-hero-bg);'; @endphp
<section class="py-20 md:py-28 px-4 text-center text-white" style="{{ $bg }}">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $content['headline'] ?? ($page->title ?? '') }}</h1>
        @if(!empty($content['subheadline']))
            <p class="text-lg text-white/90 mb-6">{{ $content['subheadline'] }}</p>
        @endif
        @if(!empty($content['cta_text']) && !empty($content['cta_url']))
            <a href="{{ $content['cta_url'] }}" class="cms-btn">{{ $content['cta_text'] }}</a>
        @endif
    </div>
</section>
