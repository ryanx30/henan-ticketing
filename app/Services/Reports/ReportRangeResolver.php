<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Normalizes report range filters into concrete date boundaries used by every report section.
 */
final class ReportRangeResolver
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function fromRequest(Request $request): array
    {
        $range = (string) $request->query('range', '1w');
        $customFrom = (string) $request->query('date_from', '');
        $customTo = (string) $request->query('date_to', '');

        return match ($range) {
            '1d' => [now()->copy()->startOfDay(), now()->copy()->endOfDay()],
            '1w', '7d', 'this_week' => [now()->copy()->subDays(6)->startOfDay(), now()->copy()->endOfDay()],
            '1m', '30d', 'this_month' => [now()->copy()->subMonth()->addDay()->startOfDay(), now()->copy()->endOfDay()],
            '3m' => [now()->copy()->subMonths(3)->addDay()->startOfDay(), now()->copy()->endOfDay()],
            'ytd' => [now()->copy()->startOfYear(), now()->copy()->endOfDay()],
            '1y', 'one_year' => [now()->copy()->subYear()->addDay()->startOfDay(), now()->copy()->endOfDay()],
            '3y' => [now()->copy()->subYears(3)->addDay()->startOfDay(), now()->copy()->endOfDay()],
            '5y' => [now()->copy()->subYears(5)->addDay()->startOfDay(), now()->copy()->endOfDay()],
            'custom' => [
                $customFrom !== '' ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->subDays(6)->startOfDay(),
                $customTo !== '' ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay(),
            ],
            default => [now()->copy()->subDays(6)->startOfDay(), now()->copy()->endOfDay()],
        };
    }
}
