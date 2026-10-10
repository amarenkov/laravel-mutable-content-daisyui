@php
    $tabs = $this->getTabs();
    $groups = collect($tabs)->groupBy('group', preserveKeys: true);
    $selected = $tab !== $this::TAB_ALL ? ($tabs[$tab]['label'] ?? null) : null;
    $groupLabels = $groups->map(fn ($items) => $items->first()['groupLabel'] ?? null)->all();
@endphp

<x-mutable-content-daisyui::collapse :title="__('mutable-content-daisyui::ui.tabs.quick_filters')"
                                     :description="$selected ? __('mutable-content-daisyui::ui.tabs.selected', ['label' => $selected]) : null"
                                     :open="$selected !== null"
                                     wire:key="quick-filters">
    @foreach ($groupLabels as $group => $groupLabel)
        @if ($groups->has($group))
            <div class="flex flex-col gap-1">
                @if ($groupLabel)
                    <span class="text-sm text-base-content/60">{{ $groupLabel }}</span>
                @endif
                <div class="flex flex-wrap gap-2">
                    @foreach ($groups[$group] as $key => $item)
                        <x-mutable-content-daisyui::button size="sm" :variant="$tab === $key ? 'primary' : null" wire:click="setTab('{{ $key }}')" wire:key="tab-{{ $key }}">
                            {{ $item['label'] }}
                            <x-mutable-content-daisyui::badge>{{ $this->tabCount($key) }}</x-mutable-content-daisyui::badge>
                        </x-mutable-content-daisyui::button>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</x-mutable-content-daisyui::collapse>
