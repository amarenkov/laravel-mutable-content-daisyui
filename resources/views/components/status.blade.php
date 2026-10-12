@props([
    'variant' => null,
    'pulse' => false,
])

<span aria-hidden="true" {{ $attributes->class([
    'status',
    'status-primary' => $variant === 'primary',
    'status-success' => $variant === 'success',
    'status-warning' => $variant === 'warning',
    'status-error' => $variant === 'error',
    'status-info' => $variant === 'info',
    'animate-pulse' => $pulse,
]) }}></span>
