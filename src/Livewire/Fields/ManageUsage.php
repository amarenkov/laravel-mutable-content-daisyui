<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Fields;

use Closure;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContent\Helpers\FieldCodeHelper;

use Amarenkov\MutableContent\Models\Field\Field as FieldModel;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModel;
use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Rules\FieldAllowedInClass;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;
use Amarenkov\MutableContentDaisyUi\Table\Column;

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
        [$type, $value] = FieldUsageModel::parseScope($scope);

        return match ($type) {
            FieldUsageModel::CODE_MUTABLE_CLASS => app(MutableClassRegistry::class)->getKeyValuePairs()[$value] ?? $value,
            FieldUsageModel::CODE_LOV_CODE => __('LOV').': '.(app(LovRegistry::class)->getLovLabel($value) ?? $value),
            default => $scope,
        };
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
        $mcds = app(MutableClassRegistry::class)->all();
        usort($mcds, fn ($a, $b) => mb_strtolower($a->label) <=> mb_strtolower($b->label));

        $lovLabels = app(LovRegistry::class)->getLovsOptions() ?? [];
        uasort($lovLabels, fn ($a, $b) => mb_strtolower((string)$a) <=> mb_strtolower((string)$b));

        $scopes = array_merge(
            array_map(fn ($mcd) => $mcd->mutableClass::getClassScope(), $mcds),
            array_map(fn ($lovCode) => FieldUsageModel::makeScope(FieldUsageModel::CODE_LOV_CODE, (string)$lovCode), array_keys($lovLabels))
        );

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
            ->disabled(fn (array $data, ?Model $record) => filled($data[FieldUsageModel::CODE_LOV_CODE] ?? null) || $isSystem($record))
            ->rules([
                'required_without:data.'.FieldUsageModel::CODE_LOV_CODE,
                'prohibits:data.'.FieldUsageModel::CODE_LOV_CODE,
                new FieldAllowedInClass($fieldCode),
            ]);

        $inputs[FieldUsageModel::CODE_LOV_CODE]
            ->live()
            ->disabled(fn (array $data, ?Model $record) => filled($data[FieldUsageModel::CODE_MUTABLE_CLASS] ?? null) || $isSystem($record))
            ->helper(__('mutable-content-daisyui::ui.usage_lov_helper'))
            ->rules([
                'required_without:data.'.FieldUsageModel::CODE_MUTABLE_CLASS,
                'prohibits:data.'.FieldUsageModel::CODE_MUTABLE_CLASS,
                function (string $attribute, mixed $value, Closure $fail) use ($fieldCode) {
                    if ($error = FieldCodeHelper::getErrorForClass($fieldCode, FieldUsageModel::LOV_MUTABLE_CLASS)) {
                        $fail($error);
                    }
                },
            ]);

        return $inputs + FieldTypeSettingsInputs::make(
            fn () => $this->fieldRecord->{Field::CODE_FIELD_TYPE},
            fn () => $this->fieldRecord->getField(Field::CODE_FIELD_TYPE_SETTINGS) ?? []
        );
    }

    protected function columns(): array
    {
        $replacedCodes = [FieldUsageModel::CODE_MUTABLE_CLASS, FieldUsageModel::CODE_LOV_CODE, ...array_keys(static::usageMarks())];

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
                [$type, $value] = FieldUsageModel::parseScope($scope);

                $text = match ($type) {
                    FieldUsageModel::CODE_MUTABLE_CLASS => $fields[$type]->label.': '.(app(MutableClassRegistry::class)->getKeyValuePairs()[$value] ?? $value),
                    FieldUsageModel::CODE_LOV_CODE => $fields[$type]->label.': '.(app(LovRegistry::class)->getLovLabel($value) ?? $value),
                    default => $scope,
                };

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

        $lovRegistry = app(LovRegistry::class);

        $filters = [
            FieldUsageModel::CODE_MUTABLE_CLASS => $this->fieldsSelectFilter(FieldUsageModel::CODE_MUTABLE_CLASS, (string)$fields[FieldUsageModel::CODE_MUTABLE_CLASS]->label, fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
            FieldUsageModel::CODE_LOV_CODE => $this->fieldsSelectFilter(FieldUsageModel::CODE_LOV_CODE, (string)$fields[FieldUsageModel::CODE_LOV_CODE]->label, fn () => $lovRegistry->getLovsOptions() ?? []),
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

    public function mount(string $field): void
    {
        $this->fieldRecord = FieldModel::query()->where('fields->'.Field::COMMON_CODE_CODE, $field)->firstOrFail();
    }

    public function title(): string
    {
        return __('mutable-content-daisyui::ui.usage_title', ['field' => $this->fieldTitle()]);
    }

    public function breadcrumbs(): array
    {
        return [
            route(MutableContentDaisyUi::ROUTE_FIELDS) => __('mutable-content-daisyui::ui.fields'),
            $this->fieldTitle(),
        ];
    }
}
