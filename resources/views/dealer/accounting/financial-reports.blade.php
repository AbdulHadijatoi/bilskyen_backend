@extends('layouts.dealer')

@section('title', 'Financial Reports - Accounting - Dealer Panel')

@section('content')
<div class="flex w-full flex-col gap-2">
    <div class="flex justify-between gap-3 max-sm:flex-col sm:items-center">
        <div>
            <h2 class="text-xl font-bold">Financial Reports</h2>
            <p class="text-muted-foreground max-w-3xl">
                Generate and view financial reports for your dealership.
            </p>
        </div>
    </div>

    <hr class="my-3 border-border">

    <!-- Financial Reports Tabs -->
    <div class="rounded-lg border border-border bg-card">
        <div class="border-b border-border">
            <div class="flex overflow-hidden">
                <div class="flex flex-1">
                    <div class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
                        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-background text-foreground shadow-sm" data-tab="income-statement">
                            Income Statement
                        </button>
                        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50" data-tab="balance-sheet">
                            Balance Sheet
                        </button>
                        <button class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50" data-tab="cash-flow">
                            Cash Flow Statement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <div id="income-statement-content" class="tab-content">
                <p class="text-muted-foreground text-center">Income Statement form will be implemented here</p>
            </div>
            <div id="balance-sheet-content" class="tab-content hidden">
                <p class="text-muted-foreground text-center">Balance Sheet form will be implemented here</p>
            </div>
            <div id="cash-flow-content" class="tab-content hidden">
                <p class="text-muted-foreground text-center">Cash Flow Statement form will be implemented here</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('[data-tab]');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Update active tab
            tabs.forEach(t => {
                t.classList.remove('bg-background', 'text-foreground', 'shadow-sm');
                t.classList.add('text-muted-foreground');
            });
            this.classList.add('bg-background', 'text-foreground', 'shadow-sm');
            this.classList.remove('text-muted-foreground');
            
            // Show/hide content
            contents.forEach(content => {
                content.classList.add('hidden');
            });
            
            if (tabName === 'income-statement') {
                document.getElementById('income-statement-content').classList.remove('hidden');
            } else if (tabName === 'balance-sheet') {
                document.getElementById('balance-sheet-content').classList.remove('hidden');
            } else if (tabName === 'cash-flow') {
                document.getElementById('cash-flow-content').classList.remove('hidden');
            }
        });
    });
});
</script>
@endsection

