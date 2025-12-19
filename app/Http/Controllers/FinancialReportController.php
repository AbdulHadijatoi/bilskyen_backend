<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportController extends Controller
{
    /**
     * Get balance sheet
     */
    public function getBalanceSheet(Request $request): JsonResponse
    {
        $asOfDate = Carbon::createFromFormat('d-m-Y', $request->input('asOfDate'));

        $accounts = FinancialAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->with(['transactionEntries' => function ($query) use ($asOfDate) {
                $query->join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
                    ->where('transactions.date', '<=', $asOfDate);
            }])
            ->get();

        $balanceSheet = $accounts->map(function ($account) {
            $debits = $account->transactionEntries->where('type', 'debit')->sum('amount');
            $credits = $account->transactionEntries->where('type', 'credit')->sum('amount');

            if (in_array($account->type, ['asset', 'expense'])) {
                $balance = $debits - $credits;
            } else {
                $balance = $credits - $debits;
            }

            return [
                'account' => $account->name,
                'type' => $account->type,
                'category' => $account->category,
                'balance' => round($balance, 2),
            ];
        })->sortBy(function ($item) {
            $order = ['asset' => 1, 'liability' => 2, 'equity' => 3];
            return $order[$item['type']] ?? 999;
        })->values();

        return response()->json($balanceSheet);
    }

    /**
     * Get income statement
     */
    public function getIncomeStatement(Request $request): JsonResponse
    {
        $dateRange = $request->input('dateToDate');
        [$startDateStr, $endDateStr] = explode('...', $dateRange);
        $startDate = Carbon::createFromFormat('d-m-Y', $startDateStr);
        $endDate = Carbon::createFromFormat('d-m-Y', $endDateStr);

        if ($startDate->gt($endDate)) {
            return response()->json(['error' => 'Start date must be before end date'], 400);
        }

        $revenueAccounts = FinancialAccount::where('type', 'revenue')
            ->with(['transactionEntries' => function ($query) use ($startDate, $endDate) {
                $query->join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
                    ->whereBetween('transactions.date', [$startDate, $endDate])
                    ->where('transaction_entries.type', 'credit');
            }])
            ->get();

        $expenseAccounts = FinancialAccount::where('type', 'expense')
            ->with(['transactionEntries' => function ($query) use ($startDate, $endDate) {
                $query->join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
                    ->whereBetween('transactions.date', [$startDate, $endDate])
                    ->where('transaction_entries.type', 'debit');
            }])
            ->get();

        $revenueBreakdown = $revenueAccounts->map(function ($account) {
            return [
                'category' => $account->category,
                'amount' => round($account->transactionEntries->sum('amount'), 2),
            ];
        })->filter(fn($item) => $item['amount'] > 0);

        $expenseBreakdown = $expenseAccounts->map(function ($account) {
            return [
                'category' => $account->category,
                'amount' => round($account->transactionEntries->sum('amount'), 2),
            ];
        })->filter(fn($item) => $item['amount'] > 0);

        return response()->json([
            [
                'accountType' => 'revenue',
                'total' => round($revenueBreakdown->sum('amount'), 2),
                'breakdown' => $revenueBreakdown->values(),
            ],
            [
                'accountType' => 'expense',
                'total' => round($expenseBreakdown->sum('amount'), 2),
                'breakdown' => $expenseBreakdown->values(),
            ],
        ]);
    }

    /**
     * Get cash flow statement
     */
    public function getCashFlowStatement(Request $request): JsonResponse
    {
        $dateRange = $request->input('dateToDate');
        [$startDateStr, $endDateStr] = explode('...', $dateRange);
        $startDate = Carbon::createFromFormat('d-m-Y', $startDateStr);
        $endDate = Carbon::createFromFormat('d-m-Y', $endDateStr);

        if ($startDate->gt($endDate)) {
            return response()->json(['error' => 'Start date must be before end date'], 400);
        }

        // Get cash accounts
        $cashAccounts = FinancialAccount::where('is_cash_account', true)->pluck('id');

        // Get transactions involving cash accounts
        $cashTransactions = TransactionEntry::join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
            ->whereIn('transaction_entries.financial_account_id', $cashAccounts)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select(
                'transactions.id',
                'transactions.type',
                'transactions.narration',
                'transaction_entries.type as entry_type',
                'transaction_entries.amount'
            )
            ->get();

        // Categorize by activity type
        $operatingActivities = ['Payment', 'Expense', 'Receipt', 'Income', 'Parts Sale', 'Service Invoice', 'Bank Receipt', 'Bank Payment', 'Interest Income'];
        $investingActivities = ['Vehicle Purchase', 'Vehicle Sale', 'Vehicle Parts Purchase', 'Vehicle Parts Sale', 'Depreciation'];
        $financingActivities = ['Loan Repayment', 'Capital Introduction', 'Investment', 'Loan Disbursement', 'Capital Withdrawal', 'Drawings'];

        $activities = [
            'Operating' => [],
            'Investing' => [],
            'Financing' => [],
        ];

        foreach ($cashTransactions as $entry) {
            $activity = 'Operating';
            if (in_array($entry->type, $investingActivities)) {
                $activity = 'Investing';
            } elseif (in_array($entry->type, $financingActivities)) {
                $activity = 'Financing';
            }

            $direction = $entry->entry_type === 'debit' ? 'inflow' : 'outflow';
            $amount = $entry->amount;

            $activities[$activity][] = [
                'narration' => $entry->narration,
                'direction' => $direction,
                'amount' => round($amount, 2),
            ];
        }

        // Calculate net for each activity
        $result = [];
        foreach ($activities as $activity => $items) {
            $inflow = collect($items)->where('direction', 'inflow')->sum('amount');
            $outflow = collect($items)->where('direction', 'outflow')->sum('amount');
            $net = $inflow - $outflow;

            $result[] = [
                'activity' => $activity,
                'items' => $items,
                'net' => round($net, 2),
            ];
        }

        return response()->json($result);
    }
}

