<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function __construct(
        private FileService $fileService
    ) {}
    /**
     * Validate that transaction entries are balanced
     */
    public function validateBalancedEntries(array $entries): void
    {
        $debitTotal = collect($entries)
            ->where('type', 'debit')
            ->sum('amount');

        $creditTotal = collect($entries)
            ->where('type', 'credit')
            ->sum('amount');

        if (abs($debitTotal - $creditTotal) > 0.01) {
            throw new \Exception('Transaction entries must be balanced. Total debits: ' . $debitTotal . ', Total credits: ' . $creditTotal);
        }

        if ($debitTotal == 0 && $creditTotal == 0) {
            throw new \Exception('Transaction entries cannot all be zero');
        }

        if (count($entries) < 2) {
            throw new \Exception('Transaction must have at least 2 entries');
        }

        $hasDebit = collect($entries)->contains('type', 'debit');
        $hasCredit = collect($entries)->contains('type', 'credit');

        if (!$hasDebit || !$hasCredit) {
            throw new \Exception('Transaction must have at least one debit and one credit entry');
        }
    }

    /**
     * Create a transaction with entries
     */
    public function createTransaction(array $transactionData, array $entries): Transaction
    {
        $this->validateBalancedEntries($entries);

        return DB::transaction(function () use ($transactionData, $entries) {
            $transaction = Transaction::create($transactionData);

            foreach ($entries as $entry) {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'financial_account_id' => $entry['financial_account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'description' => $entry['description'] ?? null,
                ]);
            }

            return $transaction->load('entries.financialAccount');
        });
    }

    /**
     * Update a transaction with new entries
     */
    public function updateTransaction(Transaction $transaction, array $transactionData, array $entries): Transaction
    {
        $this->validateBalancedEntries($entries);

        return DB::transaction(function () use ($transaction, $transactionData, $entries) {
            // Delete old images if new ones are provided
            if (isset($transactionData['images']) && is_array($transactionData['images'])) {
                $oldImages = $transaction->images ?? [];
                if (!empty($oldImages)) {
                    $this->fileService->deleteFiles($oldImages);
                }
            }

            $transaction->update($transactionData);

            // Delete old entries
            $transaction->entries()->delete();

            // Create new entries
            foreach ($entries as $entry) {
                TransactionEntry::create([
                    'transaction_id' => $transaction->id,
                    'financial_account_id' => $entry['financial_account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                    'description' => $entry['description'] ?? null,
                ]);
            }

            return $transaction->load('entries.financialAccount');
        });
    }

    /**
     * Delete a transaction and its entries
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Delete linked purchase if exists
            $linkedPurchase = Purchase::where('transaction_id', $transaction->id)->first();
            if ($linkedPurchase) {
                $linkedPurchase->delete();
            }

            // Delete linked sale if exists
            $linkedSale = Sale::where('transaction_id', $transaction->id)->first();
            if ($linkedSale) {
                $linkedSale->delete();
            }

            // Delete transaction images
            $transactionImages = $transaction->images ?? [];
            if (!empty($transactionImages)) {
                $this->fileService->deleteFiles($transactionImages);
            }

            // Delete linked purchase/sale images
            if ($linkedPurchase && !empty($linkedPurchase->images)) {
                $this->fileService->deleteFiles($linkedPurchase->images);
            }
            if ($linkedSale && !empty($linkedSale->images)) {
                $this->fileService->deleteFiles($linkedSale->images);
            }

            // Delete entries and transaction
            $transaction->entries()->delete();
            $transaction->delete();
        });
    }
}

