@props([
    'title' => true,
])

@if ($title)
    <li {{ $attributes->class(['menu-title']) }}>{{ __('mutable-content-daisyui::ui.navigation_group') }}</li>
@endif
@foreach (\Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi::navigation() as $item)
    <li>
        <a href="{{ route($item['route']) }}" wire:navigate @class(['menu-active' => request()->routeIs(...$item['active'])])>
            {{ svg('lucide-'.$item['icon'], 'size-4') }}
            {{ $item['label'] }}
        </a>
    </li>
@endforeach
