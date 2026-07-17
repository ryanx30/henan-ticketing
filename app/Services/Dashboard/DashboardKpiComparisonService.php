<?php

namespace App\Services\Dashboard;

/**
 * Builds absolute KPI comparisons and assigns a business meaning to each change.
 */
class DashboardKpiComparisonService
{
    public const NEUTRAL = 'neutral';
    public const HIGHER_IS_BETTER = 'higher_is_better';
    public const LOWER_IS_BETTER = 'lower_is_better';

    public function compare(int $current, int $previous, string $performanceDirection = self::NEUTRAL): array
    {
        $difference = $current - $previous;
        $direction = $difference > 0 ? 'up' : ($difference < 0 ? 'down' : 'flat');

        return [
            'value' => $difference,
            'label' => $difference === 0
                ? 'No change'
                : ($difference > 0 ? '+' : '') . $difference,
            'direction' => $direction,
            'sentiment' => $this->sentiment($direction, $performanceDirection),
        ];
    }

    private function sentiment(string $direction, string $performanceDirection): string
    {
        if ($direction === 'flat' || $performanceDirection === self::NEUTRAL) {
            return 'neutral';
        }

        $isPositive = match ($performanceDirection) {
            self::HIGHER_IS_BETTER => $direction === 'up',
            self::LOWER_IS_BETTER => $direction === 'down',
            default => false,
        };

        return $isPositive ? 'positive' : 'negative';
    }
}
