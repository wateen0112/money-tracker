<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function daily(ReportFilterRequest $request): JsonResponse
    {
        return $this->buildReport($request, 'daily');
    }

    public function weekly(ReportFilterRequest $request): JsonResponse
    {
        return $this->buildReport($request, 'weekly');
    }

    public function monthly(ReportFilterRequest $request): JsonResponse
    {
        return $this->buildReport($request, 'monthly');
    }

    protected function buildReport(ReportFilterRequest $request, string $granularity): JsonResponse
    {
        $user = $request->user();
        [$from, $to] = $request->dateRange();

        $builder = Transaction::query()
            ->where('user_id', $user->id)
            ->whereBetween('occurred_at', [$from, $to]);

        // Default MySQL expressions; adjust for PostgreSQL in config if needed.
        switch ($granularity) {
            case 'daily':
                $groupExpression = 'DATE(occurred_at)';
                break;
            case 'weekly':
                $groupExpression = 'YEARWEEK(occurred_at, 3)';
                break;
            case 'monthly':
            default:
                $groupExpression = "DATE_FORMAT(occurred_at, '%Y-%m')";
                break;
        }

        $grouped = $builder
            ->selectRaw("{$groupExpression} as period")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense")
            ->groupBy(DB::raw($groupExpression))
            ->orderBy('period', 'asc')
            ->get();

        $totalIncome = (float) $grouped->sum('total_income');
        $totalExpense = (float) $grouped->sum('total_expense');
        $startingAmount = (float) optional($user->setting)->starting_amount ?? 0.0;
        $remaining = $startingAmount + $totalIncome - $totalExpense;

        $breakdown = $grouped->map(function ($row) {
            $income = (float) $row->total_income;
            $expense = (float) $row->total_expense;

            return [
                'period' => $row->period,
                'total_income' => $income,
                'total_expense' => $expense,
                'net' => $income - $expense,
            ];
        })->values();

        return response()->json([
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net' => $totalIncome - $totalExpense,
            'starting_amount' => $startingAmount,
            'remaining' => $remaining,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'grouped' => $breakdown,
        ]);
    }
}

