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
            @php($options = $input->getOptions($data, $record, $optionSearch[$input->statePath] ?? null))
            <x-mutable-content-daisyui::combobox :model="$model" :live="$input->isLive()" :disabled="$disabled" :error="(bool)$error"
                                                 :options="$options"
                                                 :icons="$input->hasOptionIcons() ? collect(array_keys($options))->mapWithKeys(fn ($value) => [$value => $input->getOptionIcon($value)])->all() : []"
                                                 :placeholder="$placeholder"
                                                 :placeholder-icon="$input->getPlaceholderIcon($data, $record)"
                                                 :nullable="$input->isPlaceholderSelectable() || blank(data_get($data, $input->statePath))"
                                                 :search-model="$input->isSearchable() ? 'optionSearch.'.$input->statePath : null" />
            @break

        @default
            <x-mutable-content-daisyui::input :model="$model" :live="$input->isLive()" :disabled="$disabled" :error="(bool)$error"
                                              :type="match ($input->type) { Input::TYPE_NUMBER, Input::TYPE_INTEGER => 'number', Input::TYPE_DATE => 'date', Input::TYPE_DATETIME => 'datetime-local', default => 'text' }"
                                              :step="match ($input->type) { Input::TYPE_NUMBER => 'any', Input::TYPE_INTEGER => '1', default => null }"
                                              :maxlength="$input->getMaxLength()"
                                              :placeholder="$placeholder"
                                              :suffix="$input->getSuffix()" />
    @endswitch
</x-mutable-content-daisyui::field>
