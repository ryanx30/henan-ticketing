<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Throwable;

/**
 * Writes audit log entries with actor context and before/after values for important business actions.
 */
class AuditLogger
{
    public static function record(
        Request $request,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityLabel = null,
        ?string $description = null,
        ?array $before = null,
        ?array $after = null
    ): void {
        try {
            $actor = $request->user();

            AuditLog::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'actor_role' => $actor?->role,
                'action' => self::normalizeKey($action),
                'entity_type' => self::normalizeKey($entityType),
                'entity_id' => $entityId,
                'entity_label' => $entityLabel,
                'description' => $description,
                'before_values' => self::sanitize($before),
                'after_values' => self::sanitize($after),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function ticketLabel($ticket): string
    {
        $rawCode = $ticket?->ticket_code ?: $ticket?->id;
        $cleanCode = preg_replace('/[\s#]+/', '', (string) $rawCode);
        $cleanCode = preg_replace('/^T-?/i', '', $cleanCode ?? '');

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    public static function changedValues(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    protected static function normalizeKey(string $value): string
    {
        return str($value)
            ->trim()
            ->replace([' ', '-'], '_')
            ->lower()
            ->toString();
    }

    protected static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $hiddenKeys = [
            'password',
            'password_confirmation',
            'remember_token',
            'attachment',
            'attachment_path',
        ];

        foreach ($hiddenKeys as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '[hidden]';
            }
        }

        return $values;
    }
}
