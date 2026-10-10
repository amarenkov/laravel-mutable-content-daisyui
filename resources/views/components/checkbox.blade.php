@props([
    'label' => null,
])

<label class="flex cursor-pointer items-center gap-2">
    <input type="checkbox" {{ $attributes->class(['checkbox checkbox-sm']) }}>
    @if ($label !== null)
        <span>{{ $label }}</span>
    @endif
</label>
