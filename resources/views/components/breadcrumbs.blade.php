@props([
    'items' => [],
])

@if ($items)
    <div {{ $attributes->class(['breadcrumbs py-0 text-sm']) }}>
        <ul>
            @foreach ($items as $url => $label)
                <li>
                    @if (is_string($url))
                        <a href="{{ $url }}" wire:navigate>{{ $label }}</a>
                    @else
                        {{ $label }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
