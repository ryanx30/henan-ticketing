<?php

namespace App\Services;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * DashboardCacheService
 *
 * Caches dashboard payloads by user, role, filters, and a lightweight version key.
 * The version key makes invalidation safe even for cache stores that do not support tags.
 */
class DashboardCacheService
{
    private const TTL_SUMMARY = 300;
    private const TTL_TREND = 900;
    private const TAG = 'dashboard';
    private const VERSION_KEY = 'dashboard:version';

    /**
     * Cache the dashboard summary with a per-user/per-filter cache key.
     */
    public function rememberSummaryForRequest(Request $request, Closure $callback): mixed
    {
        return $this->safeRemember(
            $this->summaryKeyFromRequest($request),
            self::TTL_SUMMARY,
            $callback
        );
    }

    /**
     * Backward-compatible wrapper; role-only cache should no longer be used by new code.
     */
    public function rememberSummary(string $role, Closure $callback): mixed
    {
        return $this->safeRemember(
            $this->summaryKey(['role' => $role, 'legacy' => true]),
            self::TTL_SUMMARY,
            $callback
        );
    }

    /**
     * Cache trend payloads by role/range/context and current version.
     */
    public function rememberTrend(string $role, string $range, Closure $callback, array $context = []): mixed
    {
        return $this->safeRemember(
            $this->trendKey($role, $range, $context),
            self::TTL_TREND,
            $callback
        );
    }

    /**
     * Invalidate dashboard cache globally by flushing tags or bumping the version key.
     */
    public function invalidate(): void
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags([self::TAG])->flush();
                return;
            }

            Cache::forever(self::VERSION_KEY, $this->currentVersion() + 1);
        } catch (\Throwable) {
            // Cache invalidation failure must not break the request lifecycle.
        }
    }

    /**
     * Role invalidation uses global versioning because non-tag stores cannot delete dynamic keys reliably.
     */
    public function invalidateRole(string $role): void
    {
        $this->invalidate();
    }

    /**
     * Build a stable cache key from all dashboard inputs that can change the payload.
     */
    private function summaryKeyFromRequest(Request $request): string
    {
        $user = $request->user();

        return $this->summaryKey([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'priority' => $request->query('priority', 'all'),
            'status' => $request->query('status', 'all'),
            'sla' => $request->query('sla', 'all'),
            'sort' => $request->query('sort', 'latest'),
            'inbox_period' => $request->query('inbox_period', 'today'),
        ]);
    }

    /**
     * Include version in the hash so invalidation works on file/database cache drivers too.
     */
    private function summaryKey(array $context): string
    {
        ksort($context);

        return 'dashboard_summary_' . md5(json_encode([
            'version' => $this->currentVersion(),
            'context' => $context,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Include role, range, context and version in trend keys.
     */
    private function trendKey(string $role, string $range, array $context = []): string
    {
        ksort($context);

        return 'dashboard_trend_' . md5(json_encode([
            'version' => $this->currentVersion(),
            'role' => $role,
            'range' => $range,
            'context' => $context,
        ], JSON_THROW_ON_ERROR));
    }

    private function safeRemember(string $key, int $ttl, Closure $callback): mixed
    {
        try {
            if ($this->supportsTagging()) {
                return Cache::tags([self::TAG])->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable) {
            return $callback();
        }
    }

    private function currentVersion(): int
    {
        try {
            return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
        } catch (\Throwable) {
            return 1;
        }
    }

    private function supportsTagging(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached'], true);
    }
}
