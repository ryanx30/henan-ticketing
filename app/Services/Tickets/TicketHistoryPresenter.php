<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use Carbon\Carbon;

/**
 * Maps history records into a shared export and API-friendly format.
 */
final class TicketHistoryPresenter
{
    public function headers(): array
    {
        return [
            'Ticket',
            'Resolved Date',
            'Category',
            'Team',
            'Resolution Note',
            'Duration (SLA)',
        ];
    }

    public function row(Ticket $ticket): array
    {
        return [
            $this->ticketLabel($ticket),
            $this->resolvedLabel($ticket),
            $this->categoryLabel($ticket),
            strtoupper($ticket->displayTeamCode() ?: '-'),
            $this->resolutionLabel($ticket),
            $this->durationSlaText($ticket),
        ];
    }

    public function durationSlaText(Ticket $ticket): string
    {
        $duration = $this->durationText($ticket);
        $sla = $this->slaBadge($ticket);

        if ($sla === '') {
            return $duration;
        }

        return $duration . ' (' . $sla . ')';
    }

    public function ticketLabel(Ticket $ticket): string
    {
        $ticketNumber = $ticket->ticket_code ?: $ticket->id;

        $cleanCode = preg_replace('/[\s#]+/', '', (string) $ticketNumber);
        $cleanCode = preg_replace('/^T-?/i', '', (string) $cleanCode);

        return $cleanCode ? 'T-' . $cleanCode : '-';
    }

    public function resolvedLabel(Ticket $ticket): string
    {
        $value = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at ?: $ticket->created_at;

        if (! $value) {
            return '-';
        }

        return Carbon::parse($value)->format('d M Y, H:i');
    }

    public function categoryLabel(Ticket $ticket): string
    {
        if (! $ticket->category) {
            return '-';
        }

        return str($ticket->category)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    public function resolutionLabel(Ticket $ticket): string
    {
        if ($ticket->issue_type) {
            return str($ticket->issue_type)
                ->replace('_', ' ')
                ->title()
                ->toString();
        }

        return $ticket->title ?: '-';
    }

    public function durationText(Ticket $ticket): string
    {
        $start = $ticket->created_at ? Carbon::parse($ticket->created_at) : null;
        $endValue = $ticket->resolved_at ?: $ticket->closed_at ?: $ticket->updated_at;

        if (! $start || ! $endValue) {
            return '-';
        }

        $end = Carbon::parse($endValue);
        $seconds = abs($start->diffInSeconds($end));

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($hours > 0) {
            return "{$hours}h {$minutes}m {$remainingSeconds}s";
        }

        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        return "{$remainingSeconds}s";
    }

    public function slaBadge(Ticket $ticket): string
    {
        $endValue = $ticket->resolved_at ?: $ticket->closed_at;

        if (! $ticket->sla_deadline_at || ! $endValue) {
            return '';
        }

        return Carbon::parse($endValue)->lte(Carbon::parse($ticket->sla_deadline_at))
            ? 'Met'
            : 'Breached';
    }
}
