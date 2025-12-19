<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('serial_no')->unique()->comment('Calculated serial number');
            $table->string('name', 255)->unique();
            $table->string('type', 50)->comment('Enum: asset, liability, equity, revenue, expense');
            $table->string('category', 50)->comment('Enum: FINANCIAL_ACCOUNT_TYPES categories');
            $table->boolean('is_cash_account')->default(false)->comment('Used for cash flow statement');
            $table->boolean('is_system_generated')->default(false)->comment('System accounts (e.g., Accounts Payable, Sales Revenue)');
            $table->timestamps();

            // Indexes
            $table->index('created_at', 'idx_financial_accounts_created_at');
            $table->index('type', 'idx_financial_accounts_type');
            $table->index('category', 'idx_financial_accounts_category');
            $table->index('is_cash_account', 'idx_financial_accounts_is_cash_account');
            $table->index(['type', 'category'], 'idx_financial_accounts_type_category');
            $table->fullText('name', 'idx_financial_accounts_search');
        });

        // Add complex CHECK constraint using raw SQL
        DB::statement("ALTER TABLE financial_accounts ADD CONSTRAINT chk_financial_accounts_category_type CHECK (
            (type = 'asset' AND category IN ('Current Asset', 'Non-Current Asset', 'Fixed Asset', 'Tangible Asset', 'Intangible Asset', 'Financial Asset', 'Investment Asset', 'Other Asset')) OR
            (type = 'liability' AND category IN ('Current Liability', 'Non-Current Liability', 'Long-Term Liability', 'Short-Term Liability', 'Contingent Liability', 'Other Liability')) OR
            (type = 'equity' AND category IN ('Owner''s Equity', 'Retained Earnings', 'Share Capital', 'Reserves', 'Drawings', 'Other Equity')) OR
            (type = 'revenue' AND category IN ('Operating Revenue', 'Non-Operating Revenue', 'Sales Revenue', 'Service Revenue', 'Interest Income', 'Investment Income', 'Other Income')) OR
            (type = 'expense' AND category IN ('Operating Expense', 'Non-Operating Expense', 'Cost of Sales', 'Cost of Goods Sold', 'Administrative Expense', 'Selling Expense', 'Depreciation Expense', 'Finance Expense', 'Other Expense'))
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};

