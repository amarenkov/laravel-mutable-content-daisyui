@props([
    'label' => null,
    'icon' => null,
    'badge' => null,
    'square' => false,
    'width' => 'w-72',
])

<details {{ $attributes->class(['dropdown dropdown-end']) }} x-data x-on:click.outside="$el.removeAttribute('open')">
    <summary @class(['btn btn-sm btn-ghost', 'btn-square' => $square]) @if ($square && $label) title="{{ $label }}" @endif>
        @if ($icon)
            {{ svg($icon, 'size-4') }}
        @endif
        @if (!$square)
            {{ $label }}
        @endif
        @if ($badge)
            <span class="badge badge-sm badge-primary">{{ $badge }}</span>
        @endif
    </summary>
    <div @class(['dropdown-content z-20 mt-2 flex flex-col gap-3 rounded-box border border-base-300 bg-base-100 p-4 shadow', $width])>
        {{ $slot }}
    </div>
</details>
