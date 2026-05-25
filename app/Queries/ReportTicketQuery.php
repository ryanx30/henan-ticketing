<?php

namespace App\Queries;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class ReportTicketQuery
{
    public function base(Carbon $start, Carbon $end, string $scope, User $viewer): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->whereBetween('created_at', [$start, $end]);

        $this->applyScope($query, $this->normalizeScopeForUser($scope, $viewer), $viewer);

        return $query;
    }

    public function completed(Carbon $start, Carbon $end, string $scope, User $viewer): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->whereIn('status', TicketStatus::completedValues())
            ->where(function (Builder $completed) use ($start, $end) {
                $completed->whereBetween('resolved_at', [$start, $end])
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->whereBetween('closed_at', [$start, $end]));
            });

        $this->applyScope($query, $this->normalizeScopeForUser($scope, $viewer), $viewer);

        return $query;
    }

    public function normalizeScopeForUser(string $scope, User $viewer): string
    {
        if (!in_array($scope, ['my', 'team', 'all'], true)) {
            return 'my';
        }

        if ($scope === 'all' && !($viewer->isAdmin() || $viewer->isSupervisor())) {
            return 'my';
        }

        return $scope;
    }

    public function applyScope(Builder $query, string $scope, User $viewer): void
    {
        if ($scope === 'all' && ($viewer->isAdmin() || $viewer->isSupervisor())) {
            return;
        }

        if ($viewer->isIT()) {
            if ($scope === 'team') {
                $query->forTeamCode('it');
                return;
            }

            $query->where('holder_id', $viewer->id);
            return;
        }

        if ($viewer->isCS()) {
            if ($scope === 'team') {
                $query->whereIn('created_by', User::query()->where('role', User::ROLE_CS)->select('id'));
                return;
            }

            $query->where('created_by', $viewer->id);
            return;
        }

        if ($viewer->isAdmin() || $viewer->isSupervisor()) {
            if ($scope === 'team') {
                $query->whereIn('created_by', User::query()->where('role', User::ROLE_CS)->select('id'));
                return;
            }
        }

        $query->where('created_by', $viewer->id);
    }
}
