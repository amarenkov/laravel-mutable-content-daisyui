@props([
    'label' => null,
    'model' => null,
    'live' => false,
])

<label class="label mt-6 cursor-pointer gap-3">
    <input type="checkbox" @if ($model) {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" @endif {{ $attributes->class(['toggle toggle-primary']) }}>
    @if ($label !== null)
        <span class="text-base-content">{{ $label }}</span>
    @endif
</label>
