@extends('layouts.app')

@section('title', __('messages.pages.terms_of_service.page_title') . ' | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ __('messages.pages.terms_of_service.header_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ __('messages.pages.terms_of_service.header_description') }}
            </p>
        </div>
    </section>

    <!-- Terms of Service Content Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-4xl">
                <div class="prose prose-lg max-w-none">
                    {!! $termsPageContent['terms_body'] ?? '' !!}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
