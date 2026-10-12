@props([
    'title' => null,
    'body' => false,
])

@php
    $withBody = $body || $title !== null || isset($actions);
@endphp

<div {{ $attributes->class(['card border border-base-300 bg-base-100']) }}>
    @if ($withBody)
        <div class="card-body">
            @if ($title !== null || isset($actions))
                <div class="flex flex-wrap items-start gap-2">
                    <h2 class="card-title min-w-0 flex-1 whitespace-pre-line break-words">{{ $title }}</h2>
                    {{ $actions ?? '' }}
                </div>
            @endif

            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
