@extends('layouts.app')

@section('title', 'Vehicle Listed Successfully - Bilskyen')

@push('styles')
<style>
    /* Success Page Styles */
    .success-container {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .success-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 3rem 2rem;
        max-width: 600px;
        width: 100%;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        text-align: center;
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .success-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: linear-gradient(135deg, oklch(0.7 0.2 145), oklch(0.6 0.25 145));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: scaleIn 0.5s ease-out 0.2s both;
        box-shadow: 0 8px 16px rgba(0, 74, 173, 0.2);
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }

    .success-icon svg {
        width: 48px;
        height: 48px;
        color: white;
        animation: checkmark 0.6s ease-out 0.4s both;
    }

    @keyframes checkmark {
        0% {
            stroke-dasharray: 0 50;
            stroke-dashoffset: 0;
        }
        100% {
            stroke-dasharray: 50 0;
            stroke-dashoffset: 0;
        }
    }

    .success-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--foreground);
        margin-bottom: 0.75rem;
        letter-spacing: -0.02em;
    }

    .success-message {
        font-size: 1rem;
        color: var(--muted-foreground);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .vehicle-preview {
        background: var(--muted);
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        text-align: left;
    }

    .vehicle-preview-image {
        width: 100%;
        aspect-ratio: 16 / 9;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        background: var(--background);
    }

    .vehicle-preview-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 0.5rem;
    }

    .vehicle-preview-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.875rem;
        color: var(--muted-foreground);
    }

    .vehicle-preview-detail {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .vehicle-preview-detail svg {
        width: 16px;
        height: 16px;
        color: var(--primary);
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .action-buttons {
            flex-direction: row;
            justify-content: center;
        }
    }

    .btn-feature {
        background: var(--primary);
        color: var(--primary-foreground);
        border: 1px solid var(--primary);
        border-radius: 0.5rem;
        padding: 0.875rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }

    .btn-feature:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 74, 173, 0.3);
    }

    .btn-feature:active:not(:disabled) {
        transform: translateY(0);
    }

    .btn-feature:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-view {
        background: transparent;
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        padding: 0.875rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }

    .btn-view:hover {
        background: var(--muted);
        border-color: var(--primary);
        color: var(--primary);
    }

    .featured-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: oklch(0.95 0.1 145);
        border: 1px solid oklch(0.8 0.15 145);
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: oklch(0.4 0.2 145);
        margin-bottom: 1rem;
    }

    .dark .featured-badge {
        background: oklch(0.3 0.1 145);
        border-color: oklch(0.5 0.15 145);
        color: oklch(0.7 0.2 145);
    }

    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endpush

@section('content')
<div class="success-container">
    <div class="success-card">
        <!-- Success Icon -->
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <!-- Success Title -->
        <h1 class="success-title">Vehicle Listed Successfully!</h1>
        <p class="success-message">
            Your vehicle has been published and is now live on Bilskyen. 
            You can feature it to get more visibility or view it now.
        </p>

        <!-- Featured Badge (if already featured) -->
        @if($isFeatured)
            <div class="featured-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span>Your vehicle is already featured</span>
            </div>
        @endif

        <!-- Vehicle Preview -->
        <div class="vehicle-preview">
            @if($vehicle->images && $vehicle->images->count() > 0)
                <img 
                    src="{{ asset('storage/' . $vehicle->images->first()->image_path) }}" 
                    alt="{{ $vehicle->title }}"
                    class="vehicle-preview-image"
                    onerror="this.style.display='none'"
                >
            @endif
            <h3 class="vehicle-preview-title">{{ $vehicle->title }}</h3>
            <div class="vehicle-preview-details">
                @if($vehicle->brand)
                    <div class="vehicle-preview-detail">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $vehicle->brand->name }}</span>
                    </div>
                @endif
                @if($vehicle->model)
                    <div class="vehicle-preview-detail">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ $vehicle->model->name }}</span>
                    </div>
                @endif
                @if($vehicle->price)
                    <div class="vehicle-preview-detail">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ number_format($vehicle->price, 0, ',', '.') }} DKK</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            @if(!$isFeatured && $canFeature)
                <button 
                    type="button" 
                    id="feature-btn" 
                    class="btn-feature"
                    onclick="featureVehicle()"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span>Feature My Listing</span>
                </button>
            @endif
            <a 
                href="{{ route('vehicle.detail', ['id' => $vehicle->id]) }}" 
                class="btn-view"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span>View My Vehicle</span>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function featureVehicle() {
        const btn = document.getElementById('feature-btn');
        if (!btn || btn.disabled) return;

        const originalContent = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span><span>Featuring...</span>';

        fetch('{{ route("sell-your-car.feature", ["token" => $token]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show success message
                if (window.showSnackbar) {
                    window.showSnackbar('Vehicle featured successfully!', 'success');
                }

                // Update UI
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span>Featured</span>
                `;
                btn.style.background = 'oklch(0.95 0.1 145)';
                btn.style.color = 'oklch(0.4 0.2 145)';
                btn.style.borderColor = 'oklch(0.8 0.15 145)';
                btn.style.cursor = 'default';

                // Add featured badge above buttons
                const actionButtons = document.querySelector('.action-buttons');
                if (actionButtons && !document.querySelector('.featured-badge')) {
                    const badge = document.createElement('div');
                    badge.className = 'featured-badge';
                    badge.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <span>Your vehicle is now featured</span>
                    `;
                    actionButtons.parentNode.insertBefore(badge, actionButtons);
                }
            } else {
                throw new Error(data.message || 'Failed to feature vehicle');
            }
        })
        .catch(error => {
            console.error('Error featuring vehicle:', error);
            btn.disabled = false;
            btn.innerHTML = originalContent;
            
            if (window.showSnackbar) {
                window.showSnackbar(error.message || 'Failed to feature vehicle. Please try again.', 'error');
            } else {
                alert(error.message || 'Failed to feature vehicle. Please try again.');
            }
        });
    }
</script>
@endpush
@endsection
