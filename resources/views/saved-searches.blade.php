@extends('layouts.app')

@section('title', __('messages.pages.saved_searches.title') . ' | Bilskyen')

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <div class="space-y-2">
        <h1 class="text-3xl font-bold text-foreground">{{ __('messages.pages.saved_searches.title') }}</h1>
        <p class="text-muted-foreground">{{ __('messages.pages.saved_searches.description') }}</p>
    </div>

    @if($searches->count() > 0)
    <ul class="grid w-full grid-cols-1 gap-3 md:grid-cols-2" data-saved-searches-list>
        @foreach($searches as $search)
            @php
                $query = \App\Models\SavedSearch::toVehiclesQuery($search->filters ?? []);
                $applyUrl = route('vehicles').($query !== '' ? '?'.$query : '');
            @endphp
            <li
                class="site-card flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                data-saved-search-id="{{ $search->id }}"
            >
                <div class="min-w-0 space-y-1">
                    <h2 class="truncate text-base font-semibold text-foreground">
                        {{ $search->name ?: __('messages.pages.vehicles.save_search_default_name') }}
                    </h2>
                    @if($search->created_at)
                    <p class="text-sm text-muted-foreground">
                        {{ __('messages.pages.saved_searches.saved_on', ['date' => $search->created_at->translatedFormat('j. M Y')]) }}
                    </p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a
                        href="{{ $applyUrl }}"
                        class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        {{ __('messages.pages.saved_searches.apply') }}
                    </a>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                        data-saved-search-delete
                        data-id="{{ $search->id }}"
                    >
                        {{ __('messages.pages.saved_searches.delete') }}
                    </button>
                </div>
            </li>
        @endforeach
    </ul>

    @if($searches->hasPages())
    <div class="mt-4 flex items-center justify-center gap-2">
        {{ $searches->links() }}
    </div>
    @endif
    @else
    <div class="flex items-center justify-center py-12">
        <div class="flex flex-col items-center justify-center text-center">
            <h3 class="text-lg font-semibold">{{ __('messages.pages.saved_searches.empty_title') }}</h3>
            <p class="mt-1 text-muted-foreground">{{ __('messages.pages.saved_searches.empty_description') }}</p>
            <a href="{{ route('vehicles') }}" class="mt-4 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90">
                {{ __('messages.pages.vehicles.browse_vehicles') }}
            </a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    (function () {
        const destroyUrl = (id) => @json(rtrim(route('saved-searches.destroy', ['id' => '__ID__']), '/')).replace('__ID__', encodeURIComponent(id));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        document.querySelectorAll('[data-saved-search-delete]').forEach((button) => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute('data-id');
                if (!id) {
                    return;
                }
                try {
                    const response = await fetch(destroyUrl(id), {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    if (response.ok) {
                        window.showSnackbar?.(@json(__('messages.pages.saved_searches.deleted')), 'success');
                        const row = button.closest('[data-saved-search-id]');
                        row?.remove();
                        const list = document.querySelector('[data-saved-searches-list]');
                        if (list && !list.querySelector('[data-saved-search-id]')) {
                            window.location.reload();
                        }
                    } else if (response.status === 401) {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    } else {
                        window.showSnackbar?.(@json(__('messages.pages.saved_searches.delete_fail')), 'error');
                    }
                } catch (e) {
                    window.showSnackbar?.(@json(__('messages.pages.saved_searches.delete_fail')), 'error');
                }
            });
        });
    })();
</script>
@endpush
@endsection
