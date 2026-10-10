@props([
    'error' => false,
    'model' => null,
    'live' => false,
])

<textarea @if ($model) {{ $live ? 'wire:model.live' : 'wire:model' }}="{{ $model }}" @endif {{ $attributes->merge(['rows' => 3])->class(['textarea w-full field-sizing-content', 'textarea-error' => $error]) }}></textarea>
