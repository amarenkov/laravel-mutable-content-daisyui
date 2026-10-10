@props([
    'open',
    'title' => null,
    'width' => 'max-w-3xl',
])

<dialog {{ $attributes->class(['modal']) }} x-data="{ open: $wire.entangle('{{ $open }}') }" x-effect="open ? $el.showModal() : $el.close()" x-on:close="open = false">
    <div @class(['modal-box w-11/12', $width])>
        @if ($title)
            <h3 class="mb-4 text-lg font-semibold">{{ $title }}</h3>
        @endif

        {{ $slot }}
    </div>
    <form method="dialog" class="modal-backdrop"><button>{{ __('mutable-content-daisyui::ui.cancel') }}</button></form>
</dialog>
