<section class="container mx-auto px-4 py-12">
    @if(!empty($content['title']))
        <h2 class="cms-section-title">{{ $content['title'] }}</h2>
    @endif
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($vehicles as $vehicle)
            <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="border rounded-lg p-4 hover:shadow-md block" style="border-color: var(--cms-border); background: var(--cms-surface); color: var(--cms-text);">
                <h3 class="font-semibold">{{ $vehicle->title }}</h3>
                <p class="font-bold mt-2" style="color: var(--cms-accent);">{{ number_format($vehicle->price ?? 0, 0, ',', '.') }} kr.</p>
            </a>
        @empty
            <p class="col-span-full text-center" style="color: var(--cms-muted);">No vehicles available.</p>
        @endforelse
    </div>
</section>
