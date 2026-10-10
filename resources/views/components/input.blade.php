@props([
    'error' => false,
    'size' => 'md',
    'icon' => null,
    'suffix' => null,
    'model' => null,
    'live' => false,
])

<label @class([
    'input w-full',
    'input-sm' => $size === 'sm',
    'input-error' => $error,
])>
    @if ($icon)
        {{ svg($icon, 'size-4 opacity-50') }}
    @endif
    <input @if ($model) {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" @endif {{ $attributes->merge(['type' => 'text'])->class(['grow']) }}>
    @if ($suffix)
        <span class="text-base-content/60">{{ $suffix }}</span>
    @endif
</label>
