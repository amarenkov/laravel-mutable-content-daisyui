@props([
    'label' => null,
    'required' => false,
    'error' => null,
    'helper' => null,
    'full' => false,
])

<fieldset {{ $attributes->class(['fieldset', 'md:col-span-2' => $full]) }}>
    @if ($label !== null)
        <legend class="fieldset-legend">
            {{ $label }}
            @if ($required)
                <span class="text-error">*</span>
            @endif
        </legend>
    @endif

    {{ $slot }}

    @if ($error)
        <p class="label whitespace-normal text-error">{{ $error }}</p>
    @elseif ($helper)
        <p class="label whitespace-normal">{{ $helper }}</p>
    @endif
</fieldset>
