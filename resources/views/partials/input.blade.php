@php
    use Amarenkov\MutableContentDaisyUi\Form\Input;

    $model = 'data.'.$input->statePath;
    $disabled = $input->isDisabled($data, $record);
    $error = $errors->first($model);
    $placeholder = $input->getPlaceholder($data, $record);
@endphp

<x-mutable-content-daisyui::field wire:key="input-{{ $input->statePath }}"
                                  :label="$input->type === Input::TYPE_TOGGLE ? null : $input->getLabel()"
                                  :required="$input->isRequired()"
                                  :error="$error"
                                  :helper="$input->getHelper($data, $record)"
                                  :full="$input->isFullWidth()">
    @switch($input->type)
        @case(Input::TYPE_TEXTAREA)
            <x-mutable-content-daisyui::textarea :model="$model" :live="$input->isLive()" :disabled="$disabled" :error="(bool)$error" :maxlength="$input->getMaxLength()" />
            @break

        @case(Input::TYPE_TOGGLE)
            <x-mutable-content-daisyui::toggle :model="$model" :live="$input->isLive()" :disabled="$disabled" :label="$input->getLabel()" />
            @break

        @case(Input::TYPE_SELECT)
            @if ($input->isSearchable())
                <x-mutable-content-daisyui::input type="search" size="sm" icon="heroicon-o-magnifying-glass" class="mb-1"
                                                  wire:model.live.debounce.300ms="optionSearch.{{ $input->statePath }}"
                                                  placeholder="{{ __('mutable-content-daisyui::ui.search') }}" :disabled="$disabled" />
            @endif
            <x-mutable-content-daisyui::select :model="$model" :live="$input->isLive()" :disabled="$disabled" :error="(bool)$error"
                                               :options="$input->getOptions($data, $record, $optionSearch[$input->statePath] ?? null)"
                                               :placeholder="$input->isPlaceholderSelectable() || blank(data_get($data, $input->statePath)) ? ($placeholder ?? __('mutable-content-daisyui::ui.select_placeholder')) : null" />
            @break

        @default
            <x-mutable-content-daisyui::input :model="$model" :live="$input->isLive()" :disabled="$disabled" :error="(bool)$error"
                                              :type="match ($input->type) { Input::TYPE_NUMBER, Input::TYPE_INTEGER => 'number', Input::TYPE_DATE => 'date', default => 'text' }"
                                              :step="match ($input->type) { Input::TYPE_NUMBER => 'any', Input::TYPE_INTEGER => '1', default => null }"
                                              :maxlength="$input->getMaxLength()"
                                              :placeholder="$placeholder"
                                              :suffix="$input->getSuffix()" />
    @endswitch
</x-mutable-content-daisyui::field>
