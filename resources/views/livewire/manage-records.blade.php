<div class="flex flex-col gap-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-col gap-1">
            <x-mutable-content-daisyui::breadcrumbs :items="$this->breadcrumbs()" />

            <h2 class="text-xl font-semibold">{{ $this->title() }}</h2>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($this->headerActions() as $method => $label)
                <x-mutable-content-daisyui::button size="sm" wire:click="{{ $method }}">{{ $label }}</x-mutable-content-daisyui::button>
            @endforeach

            <x-mutable-content-daisyui::button variant="primary" size="sm" icon="lucide-plus" wire:click="create">
                {{ __('mutable-content-daisyui::ui.create') }}
            </x-mutable-content-daisyui::button>
        </div>
    </div>

    @if ($topView = $this->topView())
        @include($topView)
    @endif

    <x-mutable-content-daisyui::card>
        <div class="flex flex-wrap items-center gap-2 border-b border-base-300 p-3">
            @if ($this->hasSearch())
                <div class="w-full sm:w-64">
                    <x-mutable-content-daisyui::input type="search" size="sm" icon="lucide-search"
                                                      wire:model.live.debounce.400ms="search" placeholder="{{ __('mutable-content-daisyui::ui.search') }}" />
                </div>
            @endif

            <div class="ml-auto flex gap-2">
                @if ($filters = $this->getFilters())
                    <x-mutable-content-daisyui::dropdown :label="__('mutable-content-daisyui::ui.filters.title')" icon="lucide-funnel" :badge="$this->activeFiltersCount() ?: null">
                        @foreach ($filters as $name => $filter)
                            <x-mutable-content-daisyui::field :label="$filter->label" class="py-0">
                                <x-mutable-content-daisyui::combobox size="sm" model="filters.{{ $name }}" live
                                                                     :options="$filter->getOptions()" :placeholder="__('mutable-content-daisyui::ui.filters.all')" />
                            </x-mutable-content-daisyui::field>
                        @endforeach

                        @if ($this->activeFiltersCount())
                            <x-mutable-content-daisyui::button variant="ghost" size="sm" wire:click="resetFilters">{{ __('mutable-content-daisyui::ui.filters.reset') }}</x-mutable-content-daisyui::button>
                        @endif
                    </x-mutable-content-daisyui::dropdown>
                @endif

                <x-mutable-content-daisyui::dropdown :label="__('mutable-content-daisyui::ui.columns')" icon="lucide-columns-3" square width="w-64">
                    @foreach ($this->getColumns() as $name => $column)
                        <x-mutable-content-daisyui::checkbox :label="$column->getLabel()" wire:click="toggleColumn('{{ $name }}')" :checked="$this->isColumnShown($name, $column)" />
                    @endforeach
                </x-mutable-content-daisyui::dropdown>
            </div>
        </div>

        <x-mutable-content-daisyui::table>
            <thead>
                <tr>
                    @foreach ($columns as $name => $column)
                        <th @class(['w-px' => $column->isLabelHidden(), 'text-end' => $column->isNumeric()])>
                            @if ($column->isLabelHidden())
                                <span class="sr-only">{{ $column->getLabel() }}</span>
                            @elseif ($column->isSortable())
                                <button type="button" class="inline-flex items-center gap-1 hover:text-base-content" wire:click="sortBy('{{ $name }}')">
                                    {{ $column->getLabel() }}
                                    @if ($direction = $this->sortDirectionOf($name))
                                        {{ svg($direction === 'asc' ? 'lucide-chevron-up' : 'lucide-chevron-down', 'size-4') }}
                                    @else
                                        {{ svg('lucide-chevrons-up-down', 'size-4 opacity-30') }}
                                    @endif
                                </button>
                            @else
                                {{ $column->getLabel() }}
                            @endif
                        </th>
                    @endforeach
                    <th class="w-px"><span class="sr-only">{{ __('mutable-content-daisyui::ui.actions') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    @php($url = $this->recordUrl($record))
                    <tr class="hover:bg-base-200" wire:key="record-{{ $record->getKey() }}">
                        @foreach ($columns as $name => $column)
                            <td @class(['text-end tabular-nums' => $column->isNumeric()])>
                                @if ($url)
                                    <a href="{{ $url }}" wire:navigate class="block">{{ $this->renderCell($column, $record) }}</a>
                                @else
                                    {{ $this->renderCell($column, $record) }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            <div class="flex justify-end gap-1">
                                @if ($this->canDelete($record))
                                    <x-mutable-content-daisyui::button variant="ghost-error" size="xs" square icon="lucide-trash-2"
                                                                       title="{{ __('mutable-content-daisyui::ui.delete') }}"
                                                                       wire:click="delete({{ json_encode($record->getKey()) }})"
                                                                       wire:confirm="{{ __('mutable-content-daisyui::ui.delete_confirm') }}" />
                                @endif
                                <x-mutable-content-daisyui::button variant="ghost" size="xs" square icon="lucide-square-pen"
                                                                   title="{{ __('mutable-content-daisyui::ui.edit') }}"
                                                                   wire:click="edit({{ json_encode($record->getKey()) }})" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="py-10 text-center text-base-content/60">{{ __('mutable-content-daisyui::ui.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </x-mutable-content-daisyui::table>

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-base-300 p-3 text-sm">
            <div class="flex items-center gap-2">
                <span class="text-base-content/60">{{ __('mutable-content-daisyui::ui.per_page') }}</span>
                <div class="w-20">
                    <x-mutable-content-daisyui::select size="sm" wire:model.live="perPage" :options="array_combine($this::PER_PAGE_OPTIONS, $this::PER_PAGE_OPTIONS)" />
                </div>
                <span class="text-base-content/60">{{ __('mutable-content-daisyui::ui.total', ['count' => $records->total()]) }}</span>
            </div>

            {{ $records->links() }}
        </div>
    </x-mutable-content-daisyui::card>

    <x-mutable-content-daisyui::modal open="formOpen" :title="$this->formTitle()">
        @if ($formOpen)
            <form wire:submit="save" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-x-4 gap-y-2 md:grid-cols-2">
                    @foreach ($this->getInputs() as $path => $input)
                        @if ($input->isVisible($data, $formRecord))
                            @include('mutable-content-daisyui::partials.input', ['input' => $input, 'record' => $formRecord])
                        @endif
                    @endforeach
                </div>

                <x-mutable-content-daisyui::modal-actions>
                    <x-mutable-content-daisyui::button variant="ghost" wire:click="closeForm">{{ __('mutable-content-daisyui::ui.cancel') }}</x-mutable-content-daisyui::button>
                    <x-mutable-content-daisyui::button type="submit" variant="primary">
                        <x-mutable-content-daisyui::spinner wire:loading wire:target="save" />
                        {{ __('mutable-content-daisyui::ui.save.submit') }}
                    </x-mutable-content-daisyui::button>
                </x-mutable-content-daisyui::modal-actions>
            </form>
        @endif
    </x-mutable-content-daisyui::modal>

    @if ($extraView = $this->extraView())
        @include($extraView)
    @endif

    <x-mutable-content-daisyui::notifications />
</div>
