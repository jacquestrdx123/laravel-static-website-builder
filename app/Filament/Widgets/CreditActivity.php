<?php

namespace App\Filament\Widgets;

use App\Models\CreditTransaction;
use Filament\Widgets\ChartWidget;

class CreditActivity extends ChartWidget
{
    protected ?string $heading = 'Credit Activity (Last 14 Days)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $startDate = now()->subDays(13)->startOfDay();

        $transactions = CreditTransaction::query()
            ->where('created_at', '>=', $startDate)
            ->get(['amount', 'created_at']);

        $labels = collect(range(0, 13))
            ->map(fn (int $day): string => $startDate->copy()->addDays($day)->format('M j'))
            ->all();

        $creditsAdded = array_fill(0, 14, 0);
        $creditsSpent = array_fill(0, 14, 0);

        foreach ($transactions as $transaction) {
            $dayIndex = (int) $startDate->diffInDays($transaction->created_at->copy()->startOfDay());

            if ($dayIndex < 0 || $dayIndex > 13) {
                continue;
            }

            if ($transaction->amount >= 0) {
                $creditsAdded[$dayIndex] += $transaction->amount;
            } else {
                $creditsSpent[$dayIndex] += abs($transaction->amount);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Credits added',
                    'data' => $creditsAdded,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Credits spent',
                    'data' => $creditsSpent,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
