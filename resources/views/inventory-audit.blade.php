@extends('layouts.app')

@section('title', __('messages.pages.inventory_audit.title'))

@section('content')
<div class="container mx-auto px-4 py-10 max-w-3xl">
    <h1 class="text-3xl font-bold mb-2">{{ __('messages.pages.inventory_audit.title') }}</h1>
    <p class="text-muted-foreground mb-8">{{ __('messages.pages.inventory_audit.subtitle') }}</p>

    <div id="audit-form" class="space-y-4 mb-8">
        @for ($i = 0; $i < 3; $i++)
            <div class="audit-row border rounded-lg p-4 space-y-3" data-index="{{ $i }}">
                <h2 class="font-semibold">{{ __('messages.pages.inventory_audit.vehicle_label', ['number' => $i + 1]) }}</h2>
                <input type="text" name="title" placeholder="{{ __('messages.pages.inventory_audit.title_placeholder') }}" class="w-full border rounded px-3 py-2" />
                <input type="text" name="registration" placeholder="{{ __('messages.pages.inventory_audit.registration_placeholder') }}" class="w-full border rounded px-3 py-2" />
                <div class="grid grid-cols-3 gap-3">
                    <input type="number" name="image_count" min="0" placeholder="{{ __('messages.pages.inventory_audit.photos') }}" class="border rounded px-3 py-2" />
                    <input type="number" name="equipment_count" min="0" placeholder="{{ __('messages.pages.inventory_audit.equipment') }}" class="border rounded px-3 py-2" />
                    <input type="number" name="price" min="0" placeholder="{{ __('messages.pages.inventory_audit.price') }}" class="border rounded px-3 py-2" />
                </div>
                <textarea name="description" rows="3" placeholder="{{ __('messages.pages.inventory_audit.description_placeholder') }}" class="w-full border rounded px-3 py-2"></textarea>
            </div>
        @endfor
        <button type="button" id="run-audit" class="bg-primary text-primary-foreground px-6 py-2 rounded font-medium">
            {{ __('messages.pages.inventory_audit.run_audit') }}
        </button>
    </div>

    <div id="audit-results" class="hidden">
        <div id="portfolio-summary" class="border rounded-lg p-4 mb-6 bg-muted/30"></div>
        <div id="audit-items" class="space-y-4"></div>
        <p id="benchmark-message" class="mt-6 text-sm text-muted-foreground"></p>
        <p class="mt-4">
            <a href="{{ route('signup') }}" class="text-primary font-medium underline">
                {{ __('messages.pages.inventory_audit.cta') }}
            </a>
        </p>
    </div>

    <p id="audit-error" class="text-destructive hidden"></p>
</div>

<script>
document.getElementById('run-audit').addEventListener('click', async function () {
    const rows = document.querySelectorAll('.audit-row');
    const vehicles = [];

    rows.forEach(function (row) {
        const title = row.querySelector('[name="title"]').value.trim();
        const registration = row.querySelector('[name="registration"]').value.trim();
        const description = row.querySelector('[name="description"]').value.trim();
        const imageCount = row.querySelector('[name="image_count"]').value;
        const equipmentCount = row.querySelector('[name="equipment_count"]').value;
        const price = row.querySelector('[name="price"]').value;

        if (!title && !registration && !description) {
            return;
        }

        vehicles.push({
            title: title || null,
            registration: registration || null,
            description: description,
            image_count: imageCount ? parseInt(imageCount, 10) : 0,
            equipment_count: equipmentCount ? parseInt(equipmentCount, 10) : 0,
            price: price ? parseFloat(price) : null,
        });
    });

    const errorEl = document.getElementById('audit-error');
    errorEl.classList.add('hidden');

    if (vehicles.length === 0) {
        errorEl.textContent = @json(__('messages.pages.inventory_audit.error_empty'));
        errorEl.classList.remove('hidden');
        return;
    }

    this.disabled = true;
    this.textContent = @json(__('messages.pages.inventory_audit.running'));

    try {
        const response = await fetch(@json($apiUrl), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
            },
            body: JSON.stringify({ vehicles }),
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'Request failed');
        }

        const data = payload.data || payload;
        renderResults(data);
    } catch (err) {
        errorEl.textContent = err.message || @json(__('messages.pages.inventory_audit.error_generic'));
        errorEl.classList.remove('hidden');
    } finally {
        this.disabled = false;
        this.textContent = @json(__('messages.pages.inventory_audit.run_audit'));
    }
});

function renderResults(data) {
    document.getElementById('audit-results').classList.remove('hidden');

    const portfolio = data.portfolio || {};
    document.getElementById('portfolio-summary').innerHTML =
        '<strong>' + @json(__('messages.pages.inventory_audit.portfolio_score')) + ':</strong> ' +
        (portfolio.avg_score ?? '—') + '/100' +
        (portfolio.platform_avg_score ? ' · ' + @json(__('messages.pages.inventory_audit.platform_avg')) + ': ' + portfolio.platform_avg_score : '');

    const itemsEl = document.getElementById('audit-items');
    itemsEl.innerHTML = '';

    (data.items || []).forEach(function (item) {
        const card = document.createElement('div');
        card.className = 'border rounded-lg p-4';
        const title = item.title || item.registration || ('#' + (item.vehicle_id || ''));
        let issues = (item.issues || []).map(function (issue) {
            return '<li>' + issue.message + '</li>';
        }).join('');
        card.innerHTML =
            '<div class="flex justify-between mb-2"><span class="font-medium">' + title + '</span>' +
            '<span class="font-semibold">' + item.score + '/100</span></div>' +
            (issues ? '<ul class="list-disc pl-5 text-sm">' + issues + '</ul>' : '');
        itemsEl.appendChild(card);
    });

    document.getElementById('benchmark-message').textContent = data.benchmark_message || '';
}
</script>
@endsection
