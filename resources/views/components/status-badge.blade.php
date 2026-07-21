@props(['color', 'label'])

@php
$classes = match($color) {
    'gray' => 'bg-gray-100 text-gray-600',
    'info' => 'bg-blue-50 text-blue-700',
    'warning' => 'bg-yellow-50 text-yellow-700',
    'danger' => 'bg-red-50 text-red-700',
    'success' => 'bg-green-50 text-green-700',
    default => 'bg-gray-100 text-gray-600',
};
@endphp

<span {{ $attributes->merge(['class' => "text-xs px-2 py-0.5 rounded-full {$classes}"]) }}>
    {{ $label }}
</span>
