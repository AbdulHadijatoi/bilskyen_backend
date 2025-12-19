<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Contact;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Services\FinancialAccountService;
use App\Services\TransactionService;
use App\Services\FileService;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private FinancialAccountService $financialAccountService,
        private TransactionService $transactionService,
        private FileService $fileService
    ) {}

    /**
     * Validate optional entities
     */
    public function validateOptionalEntities(?int $vehicleId = null, ?int $contactId = null): array
    {
        $results = [];

        if ($vehicleId) {
            $vehicle = Vehicle::findOrFail($vehicleId);
            $results['vehicle'] = $vehicle;
        }

        if ($contactId) {
            $contact = Contact::findOrFail($contactId);
            $results['contact'] = $contact;
        }

        return $results;
    }

    /**
     * Create transaction entries for expense based on category
     */
    public function createTransactionEntries(
        string $category,
        int $paidFromAccountId,
        float $totalAmount,
        float $paidAmount,
        array $accounts
    ): array {
        $isPendingPayment = $paidAmount < $totalAmount;
        $entries = [];

        // Determine expense type and create appropriate entries
        $operatingExpenseActivities = [
            'Payment', 'Expense', 'Parts Purchase', 'Service Job', 'Customer Refund',
            'Refund', 'Bank Payment', 'Salary Payment', 'Petty Cash', 'Warranty Claim',
            'Free Service', 'Parts Issue', 'Parts Return', 'Stock Adjustment', 'Adjustment',
            'Provision', 'Write-off', 'Interest Expense'
        ];

        $investingExpenseActivities = [
            'Vehicle Purchase', 'Vehicle Parts Purchase', 'Vehicle Insurance',
            'Vehicle Registration', 'Vehicle Parts Issue', 'Depreciation'
        ];

        $financingExpenseActivities = [
            'Loan Repayment', 'Business Loan to Owner', 'Capital Withdrawal', 'Drawings'
        ];

        if (in_array($category, $operatingExpenseActivities)) {
            // Operating expenses - debit expense, credit cash/bank
            $entries[] = [
                'financial_account_id' => $accounts['operatingExpenseAccount']->id,
                'amount' => $totalAmount,
                'type' => 'debit',
            ];
        } elseif (in_array($category, $investingExpenseActivities)) {
            // Investing expenses
            if (in_array($category, ['Vehicle Purchase', 'Vehicle Parts Purchase'])) {
                $entries[] = [
                    'financial_account_id' => $accounts['vehicleInventoryAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'debit',
                ];
            } elseif ($category === 'Depreciation') {
                $entries[] = [
                    'financial_account_id' => $accounts['operatingExpenseAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'debit',
                ];
                $entries[] = [
                    'financial_account_id' => $accounts['accumulatedDepreciationAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'credit',
                ];
            } elseif (in_array($category, ['Vehicle Insurance', 'Vehicle Registration', 'Vehicle Parts Issue'])) {
                $entries[] = [
                    'financial_account_id' => $accounts['operatingExpenseAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'debit',
                ];
            }
        } elseif (in_array($category, $financingExpenseActivities)) {
            // Financing expenses
            if ($category === 'Loan Repayment') {
                $entries[] = [
                    'financial_account_id' => $accounts['loanPayableAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'debit',
                ];
            } elseif (in_array($category, ['Capital Withdrawal', 'Drawings', 'Business Loan to Owner'])) {
                $entries[] = [
                    'financial_account_id' => $accounts['ownerEquityAccount']->id,
                    'amount' => $totalAmount,
                    'type' => 'debit',
                ];
            }
        }

        // Add the paid from entry (credit) for cash/bank payment
        $entries[] = [
            'financial_account_id' => $paidFromAccountId,
            'amount' => $paidAmount,
            'type' => 'credit',
        ];

        // Add accounts payable entry if not fully paid
        if ($isPendingPayment) {
            $entries[] = [
                'financial_account_id' => $accounts['accountsPayableAccount']->id,
                'amount' => $totalAmount - $paidAmount,
                'type' => 'credit',
            ];
        }

        return $entries;
    }

    /**
     * Create an expense
     */
    public function createExpense(array $expenseData): Expense
    {
        return DB::transaction(function () use ($expenseData) {
            // Validate optional entities
            $entities = $this->validateOptionalEntities(
                $expenseData['vehicle_id'] ?? null,
                $expenseData['contact_id'] ?? null
            );

            // Get or create financial accounts
            $accounts = [
                'vehicleInventoryAccount' => $this->financialAccountService->getOrCreateVehicleInventoryAccount(),
                'accumulatedDepreciationAccount' => $this->financialAccountService->getOrCreateAccumulatedDepreciationAccount(),
                'loanPayableAccount' => $this->financialAccountService->getOrCreateLoanPayableAccount(),
                'ownerEquityAccount' => $this->financialAccountService->getOrCreateOwnerEquityAccount(),
                'accountsPayableAccount' => $this->financialAccountService->getOrCreateAccountsPayableAccount(),
                'operatingExpenseAccount' => $this->financialAccountService->getOrCreateOperatingExpenseAccount(),
            ];

            // Validate paid from account
            $paidFromAccount = $this->financialAccountService->validateAccount(
                $expenseData['paid_from_financial_account_id']
            );

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $expenseData['category'],
                $paidFromAccount->id,
                $expenseData['total_amount'],
                $expenseData['paid_amount'],
                $accounts
            );

            // Create narration
            $narration = "{$expenseData['category']}: {$expenseData['narration']}";
            if (isset($entities['vehicle'])) {
                $narration .= " for {$entities['vehicle']->make} {$entities['vehicle']->model}";
            }
            if (isset($entities['contact'])) {
                $narration .= " paid to " . ($entities['contact']->name ?? $entities['contact']->company_name);
            }

            // Create transaction
            $transaction = $this->transactionService->createTransaction([
                'type' => $expenseData['category'],
                'date' => $expenseData['date'],
                'narration' => $narration,
                'remarks' => $expenseData['remarks'] ?? null,
                'images' => $expenseData['images'] ?? [],
            ], $transactionEntries);

            // Create expense
            $expense = Expense::create([
                'date' => $expenseData['date'],
                'narration' => $expenseData['narration'],
                'category' => $expenseData['category'],
                'payment_mode' => $expenseData['payment_mode'],
                'paid_amount' => $expenseData['paid_amount'],
                'total_amount' => $expenseData['total_amount'],
                'paid_from_financial_account_id' => $paidFromAccount->id,
                'vehicle_id' => $expenseData['vehicle_id'] ?? null,
                'contact_id' => $expenseData['contact_id'] ?? null,
                'transaction_id' => $transaction->id,
                'remarks' => $expenseData['remarks'] ?? null,
                'images' => $expenseData['images'] ?? [],
            ]);

            return $expense->load(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Update an expense
     */
    public function updateExpense(Expense $expense, array $expenseData): Expense
    {
        return DB::transaction(function () use ($expense, $expenseData) {
            // Validate optional entities
            $entities = $this->validateOptionalEntities(
                $expenseData['vehicle_id'] ?? null,
                $expenseData['contact_id'] ?? null
            );

            // Get or create financial accounts
            $accounts = [
                'vehicleInventoryAccount' => $this->financialAccountService->getOrCreateVehicleInventoryAccount(),
                'accumulatedDepreciationAccount' => $this->financialAccountService->getOrCreateAccumulatedDepreciationAccount(),
                'loanPayableAccount' => $this->financialAccountService->getOrCreateLoanPayableAccount(),
                'ownerEquityAccount' => $this->financialAccountService->getOrCreateOwnerEquityAccount(),
                'accountsPayableAccount' => $this->financialAccountService->getOrCreateAccountsPayableAccount(),
                'operatingExpenseAccount' => $this->financialAccountService->getOrCreateOperatingExpenseAccount(),
            ];

            // Validate paid from account
            $paidFromAccount = $this->financialAccountService->validateAccount(
                $expenseData['paid_from_financial_account_id']
            );

            // Create transaction entries
            $transactionEntries = $this->createTransactionEntries(
                $expenseData['category'],
                $paidFromAccount->id,
                $expenseData['total_amount'],
                $expenseData['paid_amount'],
                $accounts
            );

            // Create narration
            $narration = "{$expenseData['category']}: {$expenseData['narration']}";
            if (isset($entities['vehicle'])) {
                $narration .= " for {$entities['vehicle']->make} {$entities['vehicle']->model}";
            }
            if (isset($entities['contact'])) {
                $narration .= " paid to " . ($entities['contact']->name ?? $entities['contact']->company_name);
            }

            // Delete old images if new ones are provided
            if (isset($expenseData['images']) && is_array($expenseData['images'])) {
                $oldImages = $expense->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }
            }

            // Update transaction
            $this->transactionService->updateTransaction(
                $expense->transaction,
                [
                    'type' => $expenseData['category'],
                    'date' => $expenseData['date'],
                    'narration' => $narration,
                    'remarks' => $expenseData['remarks'] ?? null,
                    'images' => $expenseData['images'] ?? [],
                ],
                $transactionEntries
            );

            // Update expense
            $expense->update([
                'date' => $expenseData['date'],
                'narration' => $expenseData['narration'],
                'category' => $expenseData['category'],
                'payment_mode' => $expenseData['payment_mode'],
                'paid_amount' => $expenseData['paid_amount'],
                'total_amount' => $expenseData['total_amount'],
                'paid_from_financial_account_id' => $paidFromAccount->id,
                'vehicle_id' => $expenseData['vehicle_id'] ?? null,
                'contact_id' => $expenseData['contact_id'] ?? null,
                'remarks' => $expenseData['remarks'] ?? null,
                'images' => $expenseData['images'] ?? [],
            ]);

            return $expense->load(['vehicle', 'contact', 'paidFromFinancialAccount', 'transaction.entries.financialAccount']);
        });
    }

    /**
     * Delete an expense
     */
    public function deleteExpense(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            // Delete transaction and expense images
            $transactionImages = $expense->transaction->images ?? [];
            $expenseImages = $expense->images ?? [];
            
            if (!empty($transactionImages)) {
                $this->fileService->deleteFiles($transactionImages);
            }
            if (!empty($expenseImages)) {
                $this->fileService->deleteFiles($expenseImages);
            }

            // Delete transaction (cascades to entries)
            $this->transactionService->deleteTransaction($expense->transaction);

            // Delete expense
            $expense->delete();
        });
    }
}

