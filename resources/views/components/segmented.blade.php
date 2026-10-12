@props([
    'options' => [],
    'model' => null,
    'live' => true,
    'size' => 'sm',
])

@php
    $name = 'mc-seg-'.substr(md5((string)$model), 0, 12);
@endphp

<div role="radiogroup" {{ $attributes->class(['join']) }}>
    @foreach ($options as $value => $label)
        <input type="radio" name="{{ $name }}" value="{{ $value }}" aria-label="{{ $label }}"
               @if ($model) {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" @endif
               @class([
                   'join-item btn',
                   'btn-xs' => $size === 'xs',
                   'btn-sm' => $size === 'sm',
               ])>
    @endforeach
</div>
