@props(['value'])

@php
    $normalized = strtolower(trim((string) $value));
    $normalized = str_replace([' ', '-'], '_', $normalized);

    $class = match ($normalized) {
        'critical' => 'badge-priority-critical',
        'high' => 'badge-priority-high',
        'medium' => 'badge-priority-medium',
        'low' => 'badge-priority-low',
        default => 'badge-priority-default',
    };

    $label = $value
        ? ucwords(str_replace('_', ' ', strtolower((string) $value)))
        : '-';
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    {{ $label }}
</span>
