<?php

namespace App\Services\Reports;

/**
 * Formats report durations without duplicating duration logic across controllers.
 */
final class ReportDurationFormatter
{
    public function human(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return '0m';
        }

        $minutes = (int) floor($seconds / 60);

        if ($minutes <= 0) {
            return '0m';
        }

        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        if ($days > 0) {
            return $hours > 0
                ? $days . 'd ' . $hours . 'h'
                : $days . 'd';
        }

        if ($hours > 0) {
            return $remainingMinutes > 0
                ? $hours . 'h ' . $remainingMinutes . 'm'
                : $hours . 'h';
        }

        return $remainingMinutes . 'm';
    }

    public function percent(float|int $value): string
    {
        $rounded = round((float) $value, 1);

        return (floor($rounded) == $rounded ? (string) (int) $rounded : (string) $rounded) . '%';
    }
}
