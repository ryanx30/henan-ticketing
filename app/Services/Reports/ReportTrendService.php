<?php

namespace App\Services\Reports;

use App\Models\Ticket;
use App\Models\User;
use App\Queries\ReportTicketQuery;
use App\Services\AggregatedStatsReader;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds completed-ticket trend data using the same report activity rules as the cards.
 */
final class ReportTrendService
{
    public function __construct(
        private AggregatedStatsReader $statsReader,
        private ReportTicketQuery $reportTicketQuery,
        private ReportTicketDateResolver $dateResolver,
    ) {
    }

    public function build(Carbon $start, Carbon $end, string $scope, User $user, string $range): array
    {
        $isOneYear = ($range === 'one_year');

        $canUseGlobalAggregate = $scope === 'all'
            && ($user->isAdmin() || $user->isSupervisor())
            && $this->statsReader->canUsePreAggregated($start, $end);

        if ($canUseGlobalAggregate) {
            return $this->fromPreAggregatedStats($start, $end, $isOneYear);
        }

        return $isOneYear
            ? $this->fromLiveMonthlyQuery($start, $end, $scope, $user)
            : $this->fromLiveDailyQuery($start, $end, $scope, $user);
    }

    private function fromPreAggregatedStats(Carbon $start, Carbon $end, bool $isOneYear): array
    {
        $labels = [];
        $values = [];
        $groupBy = $isOneYear ? 'month' : 'day';
        $trendData = $this->statsReader->trend($start, $end, $groupBy);

        foreach ($trendData as $row) {
            $labels[] = $this->formatLabel((string) $row['label'], $isOneYear);
            $values[] = $row['resolved'] + $row['closed'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'source' => 'pre_aggregated',
        ];
    }

    private function fromLiveMonthlyQuery(Carbon $start, Carbon $end, string $scope, User $user): array
    {
        $labels = [];
        $values = [];
        $months = $this->makeMonthRange($start, $end);

        $trendRows = $this->reportTicketQuery->completed($start, $end, $scope, $user)
            ->get(['id', 'resolved_at', 'closed_at'])
            ->groupBy(fn (Ticket $ticket) => $this->dateResolver->completedAt($ticket)?->format('Y-m'));

        foreach ($months as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $values[] = $trendRows->get($key, collect())->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'source' => 'live',
        ];
    }

    private function fromLiveDailyQuery(Carbon $start, Carbon $end, string $scope, User $user): array
    {
        $labels = [];
        $values = [];
        $days = $this->makeDayRange($start, $end);

        $trendRows = $this->reportTicketQuery->completed($start, $end, $scope, $user)
            ->get(['id', 'resolved_at', 'closed_at'])
            ->groupBy(fn (Ticket $ticket) => $this->dateResolver->completedAt($ticket)?->format('Y-m-d'));

        foreach ($days as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d M');
            $values[] = $trendRows->get($key, collect())->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'source' => 'live',
        ];
    }

    private function makeDayRange(Carbon $start, Carbon $end): Collection
    {
        $days = collect();
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return $days;
    }

    private function makeMonthRange(Carbon $start, Carbon $end): Collection
    {
        $months = collect();
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        return $months;
    }

    private function formatLabel(string $label, bool $isOneYear): string
    {
        try {
            return Carbon::createFromFormat($isOneYear ? 'Y-m' : 'Y-m-d', $label)
                ->format($isOneYear ? 'M Y' : 'd M');
        } catch (\Throwable) {
            return $label;
        }
    }
}
