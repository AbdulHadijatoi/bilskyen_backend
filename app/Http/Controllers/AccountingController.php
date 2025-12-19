<?php

namespace App\Http\Controllers;

use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingController extends Controller
{
    /**
     * Get financial overview
     */
    public function getFinancialOverview(Request $request): JsonResponse
    {
        $period = $request->input('period', 'year');
        
        $periods = [
            'day' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
        ];

        [$startDate, $endDate] = $periods[$period] ?? $periods['year'];
        $previousStartDate = Carbon::parse($startDate)->sub($period)->startOf($period);
        $previousEndDate = Carbon::parse($endDate)->sub($period)->endOf($period);

        // Get revenue
        $revenue = $this->calculateAccountTypeTotal('revenue', $startDate, $endDate);
        $previousRevenue = $this->calculateAccountTypeTotal('revenue', $previousStartDate, $previousEndDate);

        // Get expenses
        $expense = $this->calculateAccountTypeTotal('expense', $startDate, $endDate);
        $previousExpense = $this->calculateAccountTypeTotal('expense', $previousStartDate, $previousEndDate);

        // Calculate net profit
        $netProfit = $revenue - $expense;
        $previousNetProfit = $previousRevenue - $previousExpense;

        // Calculate profit margin
        $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;
        $previousProfitMargin = $previousRevenue > 0 ? ($previousNetProfit / $previousRevenue) * 100 : 0;

        return response()->json([
            [
                'type' => 'Revenue',
                'value' => round($revenue, 2),
                'previousPeriodValue' => round($previousRevenue, 2),
                'percentageChange' => $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                'period' => $period,
            ],
            [
                'type' => 'Expense',
                'value' => round($expense, 2),
                'previousPeriodValue' => round($previousExpense, 2),
                'percentageChange' => $previousExpense > 0 ? round((($expense - $previousExpense) / $previousExpense) * 100, 2) : 0,
                'period' => $period,
            ],
            [
                'type' => 'Net Profit',
                'value' => round($netProfit, 2),
                'previousPeriodValue' => round($previousNetProfit, 2),
                'percentageChange' => $previousNetProfit != 0 ? round((($netProfit - $previousNetProfit) / abs($previousNetProfit)) * 100, 2) : 0,
                'period' => $period,
            ],
            [
                'type' => 'Profit Margin',
                'value' => round($profitMargin, 2),
                'previousPeriodValue' => round($previousProfitMargin, 2),
                'percentageChange' => $previousProfitMargin != 0 ? round((($profitMargin - $previousProfitMargin) / abs($previousProfitMargin)) * 100, 2) : 0,
                'period' => $period,
            ],
        ]);
    }

    /**
     * Get financial overview chart data
     */
    public function getFinancialOverviewChart(Request $request): JsonResponse
    {
        $granularity = $request->input('granularity', 'year');
        
        $data = [];
        
        // Determine date range and grouping
        $startDate = now()->startOfYear();
        $endDate = now()->endOfYear();
        
        if ($granularity === 'month') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $groupBy = DB::raw('DATE_FORMAT(transactions.date, "%Y-%m")');
        } elseif ($granularity === 'quarter') {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $groupBy = DB::raw('CONCAT(YEAR(transactions.date), "-Q", QUARTER(transactions.date))');
        } elseif ($granularity === 'week') {
            $startDate = now()->startOfWeek()->subWeeks(12);
            $endDate = now()->endOfWeek();
            $groupBy = DB::raw('YEARWEEK(transactions.date)');
        } else {
            $startDate = now()->startOfYear();
            $endDate = now()->endOfYear();
            $groupBy = DB::raw('YEAR(transactions.date)');
        }

        $results = TransactionEntry::join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
            ->join('financial_accounts', 'transaction_entries.financial_account_id', '=', 'financial_accounts.id')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->select(
                $groupBy . ' as period',
                DB::raw('SUM(CASE WHEN financial_accounts.type = "revenue" AND transaction_entries.type = "credit" THEN transaction_entries.amount ELSE 0 END) as revenue'),
                DB::raw('SUM(CASE WHEN financial_accounts.type = "expense" AND transaction_entries.type = "debit" THEN transaction_entries.amount ELSE 0 END) as expense')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        foreach ($results as $result) {
            $revenue = (float) $result->revenue;
            $expense = (float) $result->expense;
            $netProfit = $revenue - $expense;
            $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

            $data[] = [
                'periodStart' => $result->period,
                'revenue' => round($revenue, 2),
                'expense' => round($expense, 2),
                'netProfit' => round($netProfit, 2),
                'profitMargin' => round($profitMargin, 2),
            ];
        }

        return response()->json($data);
    }

    /**
     * Calculate total for account type
     */
    private function calculateAccountTypeTotal(string $type, Carbon $startDate, Carbon $endDate): float
    {
        return TransactionEntry::join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
            ->join('financial_accounts', 'transaction_entries.financial_account_id', '=', 'financial_accounts.id')
            ->where('financial_accounts.type', $type)
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->where(function ($query) use ($type) {
                if ($type === 'revenue') {
                    $query->where('transaction_entries.type', 'credit');
                } else {
                    $query->where('transaction_entries.type', 'debit');
                }
            })
            ->sum('transaction_entries.amount');
    }
}

