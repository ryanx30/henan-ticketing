<?php

namespace App\Services\Navigation;

use App\Models\ResolverMessage;
use App\Models\Ticket;
use App\Models\User;
use App\Support\NavigationMenu;

/**
 * Builds sidebar menu payloads and lightweight role-aware badge counts.
 */
final class SidebarNavigationService
{
    private const ACTIVE_QUEUE_STATUSES = ['new', 'in_progress', 'waiting_info'];

    private const BADGE_KEYS = [
        'resolver-inbox' => 'resolver_inbox',
        'my-queue' => 'my_queue',
        'team-queue' => 'team_queue',
    ];

    public function badgeCountsFor(?User $user): array
    {
        $counts = [
            'resolver_inbox' => 0,
            'my_queue' => 0,
            'team_queue' => 0,
        ];

        if (! $user) {
            return $counts;
        }

        if (NavigationMenu::canUserAccess($user, 'resolver-inbox')) {
            $counts['resolver_inbox'] = ResolverMessage::query()
                ->where('to_user_id', $user->id)
                ->where('is_read', false)
                ->whereHas('ticket', fn ($query) => $query->where('status', '<>', 'closed'))
                ->count();
        }

        if (NavigationMenu::canUserAccess($user, 'my-queue')) {
            $counts['my_queue'] = Ticket::query()
                ->forTeamCode('it')
                ->where('holder_id', $user->id)
                ->whereIn('status', self::ACTIVE_QUEUE_STATUSES)
                ->count();
        }

        if (NavigationMenu::canUserAccess($user, 'team-queue')) {
            $counts['team_queue'] = Ticket::query()
                ->forTeamCode('it')
                ->where('status', 'new')
                ->whereNull('holder_id')
                ->count();
        }

        return $counts;
    }

    public function groupsForUser(?User $user, ?array $badgeCounts = null): array
    {
        $badgeCounts ??= $this->badgeCountsFor($user);

        return collect(NavigationMenu::groupsForUser($user))
            ->map(function (array $group) use ($badgeCounts): array {
                $group['items'] = $this->decorateItems($group['items'], $badgeCounts);

                return $group;
            })
            ->all();
    }

    public function flatForUser(?User $user, ?array $badgeCounts = null): array
    {
        $badgeCounts ??= $this->badgeCountsFor($user);

        return $this->decorateItems(NavigationMenu::flatForUser($user), $badgeCounts);
    }

    private function decorateItems(array $items, array $badgeCounts): array
    {
        return array_map(function (array $item) use ($badgeCounts): array {
            $badgeKey = self::BADGE_KEYS[$item['key']] ?? null;

            $item['badge_key'] = $badgeKey;
            $item['badge_count'] = $badgeKey
                ? max(0, (int) ($badgeCounts[$badgeKey] ?? 0))
                : 0;

            return $item;
        }, $items);
    }
}
