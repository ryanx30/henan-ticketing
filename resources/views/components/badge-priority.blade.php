@props(['value'])

@php
  $class = match($value) {
    'critical' => 'bg-red-600 text-white',
    'high' => 'bg-orange-500 text-white',
    'medium' => 'bg-amber-300 text-slate-900',
    'low' => 'bg-green-600 text-white',
    default => 'bg-gray-200 text-slate-900',
  };
@endphp

<span {{ $attributes->merge(['class' => "px-3 py-1 rounded-full text-xs font-semibold {$class}"]) }}>
  {{ ucfirst($value) }}
</span>