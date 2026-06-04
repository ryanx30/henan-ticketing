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
        $range = (string) $request->query('range', 'this_week');
        $customFrom = (string) $request->query('date_from', '');
        $customTo = (string) $request->query('date_to', '');

        return match ($range) {
            '7d' => [now()->copy()->subDays(6)->startOfDay(), now()->copy()->endOfDay()],
            '30d' => [now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay()],
            'this_month' => [now()->copy()->startOfMonth(), now()->copy()->endOfDay()],
            'one_year' => [now()->copy()->subMonths(11)->startOfMonth(), now()->copy()->endOfDay()],
            'custom' => [
                $customFrom !== '' ? Carbon::parse($customFrom)->startOfDay() : now()->copy()->startOfWeek(),
                $customTo !== '' ? Carbon::parse($customTo)->endOfDay() : now()->copy()->endOfDay(),
            ],
            default => [now()->copy()->startOfWeek(), now()->copy()->endOfDay()],
        };
    }
}
