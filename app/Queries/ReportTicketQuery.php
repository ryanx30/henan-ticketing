<?php

namespace App\Queries;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Centralizes report activity queries so totals, cards, chart, SLA, rows, and exports use the same base data.
 */
final class ReportTicketQuery
{
    public function base(Carbon $start, Carbon $end, string $scope, User $viewer, ?int $selectedUserId = null): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->whereBetween('created_at', [$start, $end]);

        $this->applyScope($query, $this->normalizeScopeForUser($scope, $viewer), $viewer, $selectedUserId);

        return $query;
    }

    public function completed(Carbon $start, Carbon $end, string $scope, User $viewer, ?int $selectedUserId = null): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->whereIn('status', TicketStatus::completedValues())
            ->where(function (Builder $completed) use ($start, $end) {
                $completed->whereBetween('resolved_at', [$start, $end])
                    ->orWhere(fn (Builder $fallback) => $fallback->whereNull('resolved_at')->whereBetween('closed_at', [$start, $end]));
            });

        $this->applyScope($query, $this->normalizeScopeForUser($scope, $viewer), $viewer, $selectedUserId);

        return $query;
    }


    /**
     * Report activity includes tickets created in the range and tickets completed in the range.
     * This keeps total, completed, trend, table, and SLA calculations aligned.
     */
    // ========= REPORT ACTIVITY QUERY =========

    public function activity(Carbon $start, Carbon $end, string $scope, User $viewer, ?int $selectedUserId = null): Builder
    {
        $query = Ticket::query()
            ->with(['creator', 'holder', 'teamMaster', 'priorityMaster'])
            ->where(function (Builder $range) use ($start, $end) {
                $range->whereBetween('created_at', [$start, $end])
                    ->orWhereBetween('resolved_at', [$start, $end])
                    ->orWhere(fn (Builder $fallback) => $fallback
                        ->whereNull('resolved_at')
                        ->whereBetween('closed_at', [$start, $end]));
            });

        $this->applyScope($query, $this->normalizeScopeForUser($scope, $viewer), $viewer, $selectedUserId);

        return $query;
    }

    public function normalizeScopeForUser(string $scope, User $viewer): string
    {
        if ($viewer->isAdmin()) {
            return 'it_performance';
        }

        if ($viewer->isHeadCS()) {
            return 'cs_performance';
        }

        if ($viewer->isIT()) {
            return $scope === 'team' ? 'team' : 'my';
        }

        if ($viewer->isCS()) {
            return 'my';
        }

        if ($viewer->isSupervisor()) {
            return 'all';
        }

        return 'my';
    }

    public function applyScope(Builder $query, string $scope, User $viewer, ?int $selectedUserId = null): void
    {
        if ($this->applySelectedUserScope($query, $viewer, $selectedUserId)) {
            return;
        }

        if ($scope === 'it_performance' && $viewer->isAdmin()) {
            $query->whereIn('holder_id', User::query()->where('role', User::ROLE_IT)->select('id'));
            return;
        }

        if ($scope === 'cs_performance' && $viewer->isHeadCS()) {
            $query->whereIn('created_by', User::query()->where('role', User::ROLE_CS)->select('id'));
            return;
        }

        if ($scope === 'all' && $viewer->isSupervisor()) {
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
            $query->where('created_by', $viewer->id);
            return;
        }

        $query->where('created_by', $viewer->id);
    }

    public function canUseUserFilter(User $viewer, int $selectedUserId): bool
    {
        if ($viewer->isHeadCS()) {
            return User::query()
                ->whereKey($selectedUserId)
                ->where('role', User::ROLE_CS)
                ->exists();
        }

        if ($viewer->isAdmin()) {
            return User::query()
                ->whereKey($selectedUserId)
                ->where('role', User::ROLE_IT)
                ->exists();
        }

        return false;
    }

    public function userFilterOptions(User $viewer): array
    {
        if ($viewer->isHeadCS()) {
            return $this->makeUserFilterOptions(User::ROLE_CS);
        }

        if ($viewer->isAdmin()) {
            return $this->makeUserFilterOptions(User::ROLE_IT);
        }

        return [];
    }

    private function applySelectedUserScope(Builder $query, User $viewer, ?int $selectedUserId): bool
    {
        if (!$selectedUserId) {
            return false;
        }

        if ($viewer->isHeadCS() && $this->canUseUserFilter($viewer, $selectedUserId)) {
            $query->where('created_by', $selectedUserId);
            return true;
        }

        if ($viewer->isAdmin() && $this->canUseUserFilter($viewer, $selectedUserId)) {
            $query->where('holder_id', $selectedUserId);
            return true;
        }

        return false;
    }

    private function makeUserFilterOptions(string $role): array
    {
        return User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->values()
            ->all();
    }
}
