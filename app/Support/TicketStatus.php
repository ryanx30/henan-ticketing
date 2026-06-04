<?php

namespace App\Support;

/**
 * Defines canonical ticket status values and display helpers used by workflow and presentation code.
 */
final class TicketStatus
{
    public const NEW = 'new';
    public const IN_PROGRESS = 'in_progress';
    public const WAITING_INFO = 'waiting_info';
    public const RESOLVED = 'resolved';
    public const CLOSED = 'closed';

    public const VALUES = [
        self::NEW,
        self::IN_PROGRESS,
        self::WAITING_INFO,
        self::RESOLVED,
        self::CLOSED,
    ];

    private const ALIASES = [
        'ongoing' => self::IN_PROGRESS,
        'on_going' => self::IN_PROGRESS,
        'on-going' => self::IN_PROGRESS,
        'in progress' => self::IN_PROGRESS,
    ];

    private const LABELS = [
        self::NEW => 'New',
        self::IN_PROGRESS => 'In Progress',
        self::WAITING_INFO => 'Waiting Info',
        self::RESOLVED => 'Resolved',
        self::CLOSED => 'Closed',
    ];

    public static function normalize(?string $status): string
    {
        $status = str((string) $status)
            ->trim()
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();

        return self::ALIASES[$status] ?? $status;
    }

    public static function isCanonical(?string $status): bool
    {
        return in_array(self::normalize($status), self::VALUES, true);
    }

    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::VALUES);
    }

    public static function label(?string $status): string
    {
        $status = self::normalize($status);

        return self::LABELS[$status] ?? '-';
    }

    public static function activeValues(): array
    {
        return [self::NEW, self::IN_PROGRESS, self::WAITING_INFO];
    }

    public static function completedValues(): array
    {
        return [self::RESOLVED, self::CLOSED];
    }
}
