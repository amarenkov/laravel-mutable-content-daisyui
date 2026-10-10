@props([
    'options' => [],
    'placeholder' => null,
    'error' => false,
    'size' => 'md',
    'model' => null,
    'live' => false,
])

<select @if ($model) {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" @endif {{ $attributes->class([
    'select w-full',
    'select-sm' => $size === 'sm',
    'select-error' => $error,
]) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
