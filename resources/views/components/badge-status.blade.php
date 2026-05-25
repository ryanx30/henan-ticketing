@props(['value'])

@php
    $normalized = strtolower(trim((string) $value));
    $normalized = str_replace([' ', '-'], '_', $normalized);

    $normalized = match ($normalized) {
        'ongoing', 'on_going' => 'in_progress',
        'waiting', 'waiting_user' => 'waiting_info',
        default => $normalized,
    };

    $class = match ($normalized) {
        'new' => 'badge-status-new',
        'in_progress' => 'badge-status-ongoing',
        'waiting_info' => 'badge-status-waiting',
        'resolved' => 'badge-status-resolved',
        'closed' => 'badge-status-closed',
        default => 'badge-status-default',
    };

    $label = match ($normalized) {
        'in_progress' => 'Ongoing',
        'waiting_info' => 'Waiting Info',
        'new' => 'New',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        default => $value ? ucwords(str_replace('_', ' ', strtolower((string) $value))) : '-',
    };
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    {{ $label }}
</span>
