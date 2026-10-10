@props([
    'variant' => null,
    'size' => 'md',
    'square' => false,
    'icon' => null,
    'type' => 'button',
])

<button type="{{ $type }}" {{ $attributes->class([
    'btn',
    'btn-primary' => $variant === 'primary',
    'btn-ghost' => $variant === 'ghost',
    'btn-error' => $variant === 'error',
    'btn-ghost text-error' => $variant === 'ghost-error',
    'btn-xs' => $size === 'xs',
    'btn-sm' => $size === 'sm',
    'btn-square' => $square,
]) }}>
    @if ($icon)
        {{ svg($icon, 'size-4') }}
    @endif
    {{ $slot }}
</button>
