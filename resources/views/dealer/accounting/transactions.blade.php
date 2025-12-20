@extends('layouts.dealer')

@section('title', 'Transactions - Accounting - Dealer Panel')

@section('content')
<div class="flex w-full flex-col gap-2">
    <div class="flex justify-between gap-3 max-sm:flex-col sm:items-center">
        <div>
            <h2 class="text-xl font-bold">General Ledger Entries</h2>
            <p class="text-muted-foreground max-w-3xl">
                View and manage all general ledger entries. You can add, edit, or
                delete transactions as needed. Ensure that all transactions are
                accurately recorded for proper financial reporting.
            </p>
        </div>

        <a href="/dealer/accounting/add-transaction" class="inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] ml-auto">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M5 12h14"></path>
                <path d="M12 5v14"></path>
            </svg>
            Add Transaction
        </a>
    </div>

    <hr class="my-3 border-border">

    <!-- Placeholder Data Table -->
    <div class="rounded-lg border border-border bg-card">
        <div class="p-6">
            <p class="text-muted-foreground text-center">Transactions data table will be implemented here</p>
        </div>
    </div>
</div>
@endsection

