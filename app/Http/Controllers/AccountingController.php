<?php

namespace App\Http\Controllers;

use App\Models\TransactionEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AccountingController extends Controller
{
    /**
     * Get financial overview
     */
    public function getFinancialOverview(Request $request): JsonResponse
    {
        $period = $request->input('period', 'year');

        if (! $this->accountingTablesReady()) {
            return response()->json($this->emptyFinancialOverview($period));
        }
        
        $periods = [
            'day' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
        ];

        [$startDate, $endDate] = $periods[$period] ?? $periods['year'];
        [$previousStartDate, $previousEndDate] = $this->previousPeriodBounds($period);

        try {
            $revenue = $this->calculateAccountTypeTotal('revenue', $startDate, $endDate);
            $previousRevenue = $this->calculateAccountTypeTotal('revenue', $previousStartDate, $previousEndDate);
            $expense = $this->calculateAccountTypeTotal('expense', $startDate, $endDate);
            $previousExpense = $this->calculateAccountTypeTotal('expense', $previousStartDate, $previousEndDate);
        } catch (QueryException $e) {
            if ($this->isMissingTableException($e)) {
                return response()->json($this->emptyFinancialOverview($period));
            }

            throw $e;
        }

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
        if (! $this->accountingTablesReady()) {
            return response()->json([]);
        }

        $granularity = $request->input('granularity', 'year');

        $periodExpression = match ($granularity) {
            'month' => 'DATE_FORMAT(transactions.date, "%Y-%m")',
            'quarter' => 'CONCAT(YEAR(transactions.date), "-Q", QUARTER(transactions.date))',
            'week' => 'YEARWEEK(transactions.date)',
            default => 'YEAR(transactions.date)',
        };

        [$startDate, $endDate] = match ($granularity) {
            'week' => [now()->startOfWeek()->subWeeks(12), now()->endOfWeek()],
            'month', 'quarter' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfYear(), now()->endOfYear()],
        };

        try {
            $results = TransactionEntry::join('transactions', 'transaction_entries.transaction_id', '=', 'transactions.id')
                ->join('financial_accounts', 'transaction_entries.financial_account_id', '=', 'financial_accounts.id')
                ->whereBetween('transactions.date', [$startDate, $endDate])
                ->selectRaw(
                    "{$periodExpression} as period,
                SUM(CASE WHEN financial_accounts.type = 'revenue' AND transaction_entries.type = 'credit' THEN transaction_entries.amount ELSE 0 END) as revenue,
                SUM(CASE WHEN financial_accounts.type = 'expense' AND transaction_entries.type = 'debit' THEN transaction_entries.amount ELSE 0 END) as expense"
                )
                ->groupByRaw($periodExpression)
                ->orderByRaw($periodExpression)
                ->get();
        } catch (QueryException $e) {
            if ($this->isMissingTableException($e)) {
                return response()->json([]);
            }

            throw $e;
        }

        $data = [];

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
        if (! $this->accountingTablesReady()) {
            return 0.0;
        }

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

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function previousPeriodBounds(string $period): array
    {
        $now = now();

        return match ($period) {
            'day' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            default => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
        };
    }

    private function accountingTablesReady(): bool
    {
        foreach (['financial_accounts', 'transactions', 'transaction_entries'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function isMissingTableException(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;

        return $sqlState === '42S02'
            || str_contains($e->getMessage(), "Base table or view not found");
    }

    /**
     * @return list<array{type: string, value: float, previousPeriodValue: float, percentageChange: int, period: string}>
     */
    private function emptyFinancialOverview(string $period): array
    {
        $empty = static fn (string $type) => [
            'type' => $type,
            'value' => 0.0,
            'previousPeriodValue' => 0.0,
            'percentageChange' => 0,
            'period' => $period,
        ];

        return [
            $empty('Revenue'),
            $empty('Expense'),
            $empty('Net Profit'),
            $empty('Profit Margin'),
        ];
    }
}


