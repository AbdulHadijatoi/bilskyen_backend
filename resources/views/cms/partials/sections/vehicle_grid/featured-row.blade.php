<section class="container mx-auto px-4 py-12">
    @if(!empty($content['title']))
        <h2 class="cms-section-title">{{ $content['title'] }}</h2>
    @endif
    <div class="flex gap-4 overflow-x-auto pb-2">
        @forelse($vehicles as $vehicle)
            <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="border rounded-lg p-4 hover:shadow-md block min-w-[240px] shrink-0" style="border-color: var(--cms-border); background: var(--cms-surface); color: var(--cms-text);">
                <h3 class="font-semibold">{{ $vehicle->title }}</h3>
                <p class="font-bold mt-2" style="color: var(--cms-accent);">{{ number_format($vehicle->price ?? 0, 0, ',', '.') }} kr.</p>
            </a>
        @empty
            <p style="color: var(--cms-muted);">No vehicles available.</p>
        @endforelse
    </div>
</section>
