<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Fields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContent\Models\Field\Field as FieldModel;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModel;
use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Rules\FieldAllowedInClass;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Form\Input;
use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;
use Amarenkov\MutableContentDaisyUi\Table\Column;
use Amarenkov\MutableContentDaisyUi\Table\Filter;

class ManageUsage extends ManageRecords
{
    // static
    protected static string $model = FieldUsageModel::class;

    /**
     * Boolean usage fields shown as icons in the usage column.
     *
     * @return array<string, string>
     */
    protected static function usageMarks(): array
    {
        return [
            Field::COMMON_CODE_IS_SYSTEM => 'lock',
            FieldUsageModel::CODE_IS_REQUIRED => 'circle-alert',
            FieldUsageModel::CODE_IS_IMMUTABLE => 'ban',
            FieldUsageModel::CODE_IS_IMMUTABLE_FOR_SYSTEM_OBJECTS => 'shield-alert',
        ];
    }

    public static function getScopeTitle(string $scope): string
    {
        $mutableClass = FieldUsageModel::getScopeMutableClass($scope);

        if ($mutableClass === null) {
            return $scope;
        }

        $title = app(MutableClassRegistry::class)->getKeyValuePairs()[$mutableClass] ?? $mutableClass;
        $typeCode = FieldUsageModel::getScopeTypeCode($scope);

        if ($typeCode === null) {
            return $title;
        }

        $typeLabel = is_subclass_of($mutableClass, ModelWithFields::class) ? ($mutableClass::getTypeOptions()[$typeCode] ?? $typeCode) : $typeCode;

        return $title.': '.$typeLabel;
    }

    /**
     * Scopes in display order with their titles: each class by label followed by its types.
     *
     * @return array<string, string>
     */
    public static function orderedScopes(): array
    {
        $mcds = app(MutableClassRegistry::class)->all();
        usort($mcds, fn ($a, $b) => mb_strtolower($a->label) <=> mb_strtolower($b->label));

        $result = [];

        foreach ($mcds as $mcd) {
            $mutableClass = $mcd->mutableClass;

            $result[$mutableClass::getClassScope()] = $mcd->label;

            $types = $mutableClass::getTypeOptions();
            uasort($types, fn ($a, $b) => mb_strtolower($a) <=> mb_strtolower($b));

            foreach ($types as $typeCode => $typeLabel) {
                $result[$mutableClass::getTypeScope((string)$typeCode)] = $mcd->label.': '.$typeLabel;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    protected static function typeOptionsOf(mixed $mutableClass): array
    {
        return is_string($mutableClass) && is_subclass_of($mutableClass, ModelWithFields::class) ? $mutableClass::getTypeOptions() : [];
    }

    // protected
    protected function parentRelation(): ?HasMany
    {
        return $this->fieldRecord->usages();
    }

    protected function fieldTitle(): string
    {
        return $this->fieldRecord->label() !== '' ? $this->fieldRecord->label() : $this->fieldRecord->code();
    }

    protected function applyDefaultOrder(Builder $query, string $direction): void
    {
        $scopes = array_keys(static::orderedScopes());

        if (!$scopes) {
            return;
        }

        $cases = implode(' ', array_map(fn ($i) => 'WHEN ? THEN '.$i, array_keys($scopes)));

        $last = $direction === 'desc' ? -1 : count($scopes);

        $query->orderByRaw(
            'CASE '.FieldUsageModel::CODE_SCOPE.' '.$cases.' ELSE '.$last.' END '.($direction === 'desc' ? 'desc' : 'asc'),
            $scopes
        );
    }

    protected function inputs(): array
    {
        $inputs = parent::inputs();

        $fieldCode = $this->fieldRecord->code();

        $isSystem = fn (?Model $record) => $record instanceof ModelWithFields && $record->isSystem();

        $inputs[FieldUsageModel::CODE_MUTABLE_CLASS]
            ->live()
            ->required()
            ->disabled(fn (array $data, ?Model $record) => $isSystem($record))
            ->rules([new FieldAllowedInClass($fieldCode)]);

        $inputs[FieldUsageModel::CODE_TYPE_CODE]
            ->type(Input::TYPE_SELECT)
            ->options(fn (array $data) => static::typeOptionsOf($data[FieldUsageModel::CODE_MUTABLE_CLASS] ?? null))
            ->placeholder(__('mutable-content-daisyui::ui.usage_all_types'))
            ->helper(__('mutable-content-daisyui::ui.usage_type_helper'))
            ->visible(fn (array $data) => static::typeOptionsOf($data[FieldUsageModel::CODE_MUTABLE_CLASS] ?? null) !== [])
            ->disabled(fn (array $data, ?Model $record) => $isSystem($record))
            ->rules(fn (array $data) => [Rule::in(array_map('strval', array_keys(static::typeOptionsOf($data[FieldUsageModel::CODE_MUTABLE_CLASS] ?? null))))]);

        return $inputs + FieldTypeSettingsInputs::make(
            fn () => $this->fieldRecord->{Field::CODE_FIELD_TYPE},
            fn () => $this->fieldRecord->getField(Field::CODE_FIELD_TYPE_SETTINGS) ?? []
        );
    }

    protected function payload(?Model $record): array
    {
        $payload = parent::payload($record);

        if (!array_key_exists(FieldUsageModel::CODE_TYPE_CODE, $payload) && !($record instanceof ModelWithFields && $record->isSystem())) {
            $payload[FieldUsageModel::CODE_TYPE_CODE] = null;
        }

        return $payload;
    }

    protected function columns(): array
    {
        $replacedCodes = [FieldUsageModel::CODE_MUTABLE_CLASS, FieldUsageModel::CODE_TYPE_CODE, ...array_keys(static::usageMarks())];

        $columns = [];

        foreach (parent::columns() as $name => $column) {
            if ($name === FieldUsageModel::CODE_MUTABLE_CLASS) {
                $columns['usage'] = $this->usageColumn();
                $columns[FieldUsageModel::CODE_FIELD_TYPE_SETTINGS] = $this->typeSettingsColumn();
            }

            if (!in_array($name, $replacedCodes, true)) {
                $columns[$name] = $column;
            }
        }

        return $columns;
    }

    protected function usageColumn(): Column
    {
        $fields = $this->fields();

        return Column::make('usage')
            ->label(__('mutable-content-daisyui::ui.usage'))
            ->state(fn (ModelWithFields $record) => (string)$record->{FieldUsageModel::CODE_SCOPE})
            ->format(function ($scope, ModelWithFields $record) use ($fields) {
                $text = $fields[FieldUsageModel::CODE_MUTABLE_CLASS]->label.': '.static::getScopeTitle($scope);

                $marks = [];

                foreach (static::usageMarks() as $code => $icon) {
                    if ($record->{$code} && isset($fields[$code])) {
                        $marks[] = [$icon, $fields[$code]->label];
                    }
                }

                return static::textWithMarks($text, $marks);
            });
    }

    protected function typeSettingsColumn(): Column
    {
        return Column::make(FieldUsageModel::CODE_FIELD_TYPE_SETTINGS)
            ->label(__('Type settings'))
            ->state(fn (ModelWithFields $record) => $record->getField(FieldUsageModel::CODE_FIELD_TYPE_SETTINGS))
            ->format(function ($settings) {
                $described = FieldTypeSettingsInputs::describe((string)$this->fieldRecord->{Field::CODE_FIELD_TYPE}, $settings);

                return $described ? implode(', ', $described) : null;
            });
    }

    protected function tableFilters(): array
    {
        $fields = $this->fields();

        $filters = [
            FieldUsageModel::CODE_MUTABLE_CLASS => $this->fieldsSelectFilter(FieldUsageModel::CODE_MUTABLE_CLASS, (string)$fields[FieldUsageModel::CODE_MUTABLE_CLASS]->label, fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
            FieldUsageModel::CODE_TYPE_CODE => Filter::select(
                FieldUsageModel::CODE_TYPE_CODE,
                (string)$fields[FieldUsageModel::CODE_TYPE_CODE]->label,
                fn () => array_filter(static::orderedScopes(), fn ($scope) => FieldUsageModel::getScopeTypeCode($scope) !== null, ARRAY_FILTER_USE_KEY),
                fn (Builder $query, string $scope) => $query->where(FieldUsageModel::CODE_SCOPE, $scope),
            ),
        ] + parent::tableFilters();

        foreach (array_keys(static::usageMarks()) as $code) {
            if ($code !== Field::COMMON_CODE_IS_SYSTEM && isset($fields[$code])) {
                $filters[$code] = $this->boolFilter($fields[$code]);
            }
        }

        return $filters;
    }

    // public
    public FieldModel $fieldRecord;

    public function updatedData(mixed $value, string $key): void
    {
        if ($key === FieldUsageModel::CODE_MUTABLE_CLASS) {
            $this->data[FieldUsageModel::CODE_TYPE_CODE] = null;
        }
    }

    public function mount(string $field): void
    {
        $this->fieldRecord = FieldModel::query()->where('fields->'.Field::COMMON_CODE_CODE, $field)->firstOrFail();
    }

    public function title(): string
    {
        return __('mutable-content-daisyui::ui.usage_title', ['field' => $this->fieldTitle()]);
    }

    public function recordTitle(ModelWithFields $record): string
    {
        return static::getScopeTitle((string)$record->{FieldUsageModel::CODE_SCOPE});
    }

    public function breadcrumbs(): array
    {
        return [
            route(MutableContentDaisyUi::ROUTE_FIELDS) => __('mutable-content-daisyui::ui.fields'),
            $this->fieldTitle(),
        ];
    }
}
