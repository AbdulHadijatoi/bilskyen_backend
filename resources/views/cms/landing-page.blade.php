@extends('layouts.app')

@section('content')
<div class="cms-landing">
    @foreach($page->blocks ?? [] as $block)
        @php $type = $block['type'] ?? ''; @endphp

        @if($type === 'hero')
            <section class="bg-slate-900 text-white py-16 px-4 text-center">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $block['headline'] ?? $page->title }}</h1>
                    @if(!empty($block['subheadline']))
                        <p class="text-lg text-slate-200 mb-6">{{ $block['subheadline'] }}</p>
                    @endif
                    @if(!empty($block['cta_text']) && !empty($block['cta_url']))
                        <a href="{{ $block['cta_url'] }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">{{ $block['cta_text'] }}</a>
                    @endif
                </div>
            </section>
        @elseif($type === 'richtext')
            <section class="container mx-auto px-4 py-10 max-w-3xl prose">
                {!! $block['html'] ?? '' !!}
            </section>
        @elseif($type === 'cta')
            <section class="bg-blue-50 py-12 px-4 text-center">
                <div class="max-w-2xl mx-auto">
                    <h2 class="text-2xl font-bold mb-4">{{ $block['title'] ?? '' }}</h2>
                    @if(!empty($block['button_text']) && !empty($block['button_url']))
                        <a href="{{ $block['button_url'] }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg">{{ $block['button_text'] }}</a>
                    @endif
                </div>
            </section>
        @elseif($type === 'vehicle_grid')
            <section class="container mx-auto px-4 py-12">
                @if(!empty($block['title']))
                    <h2 class="text-2xl font-bold mb-6 text-center">{{ $block['title'] }}</h2>
                @endif
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($vehicles as $vehicle)
                        <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="border rounded-lg p-4 hover:shadow-md block">
                            <h3 class="font-semibold">{{ $vehicle->title }}</h3>
                            <p class="text-blue-600 font-bold mt-2">{{ number_format($vehicle->price ?? 0, 0, ',', '.') }} kr.</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @elseif($type === 'faq')
            <section class="container mx-auto px-4 py-12 max-w-2xl">
                @if(!empty($block['title']))
                    <h2 class="text-2xl font-bold mb-6">{{ $block['title'] }}</h2>
                @endif
                <div class="space-y-4">
                    @foreach($block['items'] ?? [] as $item)
                        <details class="border rounded-lg p-4">
                            <summary class="font-medium cursor-pointer">{{ $item['question'] ?? '' }}</summary>
                            <p class="mt-2 text-gray-600">{{ $item['answer'] ?? '' }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>
@endsection
