@props(['value'])

@php
  $class = match($value) {
    'new' => 'bg-gray-200 text-slate-900',
    'in_progress' => 'bg-orange-500 text-white',
    'waiting_info' => 'bg-amber-400 text-slate-900',
    'resolved' => 'bg-green-600 text-white',
    'closed' => 'bg-sky-700 text-white',
    default => 'bg-gray-200 text-slate-900',
  };

  $label = match($value) {
    'in_progress' => 'On Going',
    'waiting_info' => 'Wait Info',
    default => ucfirst(str_replace('_',' ', $value)),
  };
@endphp

<span {{ $attributes->merge(['class' => "px-3 py-1 rounded-full text-xs font-semibold {$class}"]) }}>
  {{ $label }}
</span>