<?php

namespace App\Support;

use App\Models\Ticket;
use Carbon\Carbon;

final class TicketLabels
{
    public static function code(Ticket $ticket): string
    {
        $raw = $ticket->ticket_code ?: $ticket->id;
        $clean = preg_replace('/[\s#]+/', '', (string) $raw);
        $clean = preg_replace('/^T-?/i', '', (string) $clean);

        return $clean ? 'T-' . $clean : '-';
    }

    public static function dateTime(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('d M Y, H:i') : null;
    }

    public static function title(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        return str($value)->replace('_', ' ')->title()->toString();
    }

    public static function effectiveCompletedAt(Ticket $ticket): mixed
    {
        return $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;
    }
}
