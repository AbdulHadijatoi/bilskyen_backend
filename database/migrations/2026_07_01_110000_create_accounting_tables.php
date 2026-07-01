<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_accounts')) {
            Schema::create('financial_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('serial_no')->unique();
                $table->string('name')->unique();
                $table->string('type', 50);
                $table->string('category')->nullable();
                $table->boolean('is_cash_account')->default(false);
                $table->boolean('is_system_generated')->default(false);
                $table->timestamps();

                $table->index('type');
            });
        }

        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('serial_no')->unique();
                $table->string('type', 50)->nullable();
                $table->date('date');
                $table->text('narration')->nullable();
                $table->text('remarks')->nullable();
                $table->json('images')->nullable();
                $table->timestamps();

                $table->index('date');
            });
        }

        if (! Schema::hasTable('transaction_entries')) {
            Schema::create('transaction_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
                $table->foreignId('financial_account_id')->constrained('financial_accounts')->cascadeOnDelete();
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('type', 10);
                $table->string('description')->nullable();
                $table->timestamps();

                $table->index(['financial_account_id', 'type']);
            });
        }

        $this->seedSystemFinancialAccounts();
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('financial_accounts');
    }

    private function seedSystemFinancialAccounts(): void
    {
        $accounts = [
            ['name' => 'Vehicle Inventory', 'type' => 'asset', 'category' => 'Current Asset'],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'Current Asset'],
            ['name' => 'Sales Revenue', 'type' => 'revenue', 'category' => 'Sales Revenue'],
            ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'category' => 'Cost of Sales'],
            ['name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'Current Liability'],
            ['name' => 'Accumulated Depreciation', 'type' => 'asset', 'category' => 'Fixed Asset'],
            ['name' => 'Loan Payable', 'type' => 'liability', 'category' => 'Long-Term Liability'],
            ['name' => "Owner's Equity", 'type' => 'equity', 'category' => "Owner's Equity"],
            ['name' => 'Operating Expense', 'type' => 'expense', 'category' => 'Operating Expense'],
        ];

        $serial = (int) DB::table('financial_accounts')->max('serial_no');

        foreach ($accounts as $account) {
            if (DB::table('financial_accounts')->where('name', $account['name'])->exists()) {
                continue;
            }

            $serial++;

            DB::table('financial_accounts')->insert([
                'serial_no' => $serial,
                'name' => $account['name'],
                'type' => $account['type'],
                'category' => $account['category'],
                'is_cash_account' => false,
                'is_system_generated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
