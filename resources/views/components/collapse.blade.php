@props([
    'title',
    'description' => null,
    'open' => false,
])

<details wire:ignore.self {{ $attributes->class(['collapse collapse-arrow border border-base-300 bg-base-100']) }} @if ($open) open @endif>
    <summary class="collapse-title min-h-0 py-3 pe-10">
        <span class="font-medium">{{ $title }}</span>
        @if ($description)
            <span class="ms-2 text-sm text-base-content/60">{{ $description }}</span>
        @endif
    </summary>
    <div class="collapse-content flex flex-col gap-3">
        {{ $slot }}
    </div>
</details>
