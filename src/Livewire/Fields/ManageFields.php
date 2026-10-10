<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Fields;

use Closure;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

use Livewire\Attributes\Url;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as FieldType;
use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContent\Helpers\ObjectHelper;

use Amarenkov\MutableContent\Models\Field\Field as FieldModel;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModel;
use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Rules\FieldCode as FieldCodeRule;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Helpers\IconHelper;
use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;
use Amarenkov\MutableContentDaisyUi\Table\Column;

class ManageFields extends ManageRecords
{
    // const
    public const TAB_ALL = 'all';
    public const TAB_UNBOUND = 'unbound';

    protected const TYPE_TAB_PREFIX = 'type-';

    // static
    protected static string $model = FieldModel::class;

    protected static function boundTo(Builder $query, string $mutableClass): Builder
    {
        return $query->whereHas('usages', function (Builder $query) use ($mutableClass) {
            $query->whereIn(FieldUsageModel::CODE_SCOPE, $mutableClass::getFieldScopes());
        });
    }

    /**
     * @param class-string<ModelWithFields> $mutableClass
     */
    protected static function boundToType(Builder $query, string $mutableClass, string $typeCode): Builder
    {
        return $query->whereHas('usages', function (Builder $query) use ($mutableClass, $typeCode) {
            $query->whereIn(FieldUsageModel::CODE_SCOPE, $mutableClass::getFieldScopesForType($typeCode));
        });
    }

    // protected
    /** @var ?array<string, array{label: string, group: string, groupLabel: ?string, query: ?Closure}> */
    protected ?array $tabsCache = null;

    protected function baseQuery(): Builder
    {
        $query = parent::baseQuery();

        $tab = $this->getTabs()[$this->tab] ?? null;

        if ($tab && $tab['query']) {
            $tab['query']($query);
        }

        return $query;
    }

    protected function defaultSort(): ?string
    {
        return Field::COMMON_CODE_LABEL;
    }

    protected function fieldTypeOf(array $data, ?Model $record): ?string
    {
        if (array_key_exists(Field::CODE_FIELD_TYPE, $data)) {
            return $data[Field::CODE_FIELD_TYPE];
        }

        return $record instanceof ModelWithFields ? $record->getField(Field::CODE_FIELD_TYPE) : null;
    }

    protected function inputs(): array
    {
        $inputs = parent::inputs();

        $inputs[Field::COMMON_CODE_CODE]->rules(fn (array $data, ?Model $record) => [
            new FieldCodeRule($record instanceof FieldModel
                ? $record->usages()->pluck(FieldUsageModel::CODE_SCOPE)->map(fn ($scope) => FieldUsageModel::getScopeMutableClass($scope))->filter()->unique()->values()->all()
                : []),
        ]);

        $lovRegistry = app(LovRegistry::class);

        $inputs[Field::CODE_FIELD_TYPE]
            ->live()
            ->options(function (array $data, ?Model $record) use ($lovRegistry) {
                $options = $lovRegistry->getLovItemsOptions(FieldType::CLASS_CODE) ?? [];

                if ($record instanceof ModelWithFields && $record->getField(Field::CODE_FIELD_TYPE) === FieldType::TYPE_SYSTEM) {
                    return $options;
                }

                return array_diff_key($options, [FieldType::TYPE_SYSTEM => true]);
            });

        $inputs[Field::CODE_LOV_CODE]->visible(fn (array $data, ?Model $record) => $this->fieldTypeOf($data, $record) === FieldType::TYPE_LOV_ITEM);
        $inputs[Field::CODE_OBJECT_CLASS]->visible(fn (array $data, ?Model $record) => $this->fieldTypeOf($data, $record) === FieldType::TYPE_OBJECT);

        return $inputs + FieldTypeSettingsInputs::make(fn (array $data, ?Model $record) => $this->fieldTypeOf($data, $record));
    }

    protected function columns(): array
    {
        $detailCodes = [Field::CODE_FIELD_TYPE, Field::CODE_LOV_CODE, Field::CODE_OBJECT_CLASS];

        $columns = [];

        foreach (parent::columns() as $name => $column) {
            if ($name === Field::CODE_FIELD_TYPE) {
                $columns['details'] = $this->detailsColumn();
            }

            if (!in_array($name, $detailCodes, true)) {
                $columns[$name] = $column;
            }
        }

        return $columns;
    }

    protected function detailsColumn(): Column
    {
        $lovRegistry = app(LovRegistry::class);

        return Column::make('details')
            ->label(__('mutable-content-daisyui::ui.type'))
            ->state(fn (ModelWithFields $record) => (string)$record->{Field::CODE_FIELD_TYPE})
            ->format(function ($fieldType, ModelWithFields $record) use ($lovRegistry) {
                $result = $lovRegistry->getLovItemLabel(FieldType::CLASS_CODE, $fieldType) ?? $fieldType;

                $detail = match ($fieldType) {
                    FieldType::TYPE_LOV_ITEM => filled($record->{Field::CODE_LOV_CODE}) ? ($lovRegistry->getLovLabel($record->{Field::CODE_LOV_CODE}) ?? $record->{Field::CODE_LOV_CODE}) : null,
                    FieldType::TYPE_OBJECT => filled($record->{Field::CODE_OBJECT_CLASS}) ? ObjectHelper::getClassLabel($record->{Field::CODE_OBJECT_CLASS}) : null,
                    default => null,
                };

                if ($detail !== null) {
                    $result .= ': '.$detail;
                }

                if ($settings = FieldTypeSettingsInputs::describe($fieldType, $record->getField(Field::CODE_FIELD_TYPE_SETTINGS))) {
                    $result .= ' ('.implode(', ', $settings).')';
                }

                $icon = IconHelper::render(IconHelper::lovItemIcon(FieldType::CLASS_CODE, $fieldType));

                return new HtmlString('<span class="inline-flex items-center gap-1.5">'.$icon?->toHtml().e($result).'</span>');
            });
    }

    protected function tableFilters(): array
    {
        $lovRegistry = app(LovRegistry::class);

        return [
            Field::CODE_FIELD_TYPE => $this->fieldsSelectFilter(Field::CODE_FIELD_TYPE, __('Field type'), fn () => $lovRegistry->getLovItemsOptions(FieldType::CLASS_CODE) ?? []),
            Field::CODE_LOV_CODE => $this->fieldsSelectFilter(Field::CODE_LOV_CODE, __('LOV'), fn () => $lovRegistry->getLovsOptions() ?? []),
            Field::CODE_OBJECT_CLASS => $this->fieldsSelectFilter(Field::CODE_OBJECT_CLASS, __('Object class'), fn () => app(MutableClassRegistry::class)->getKeyValuePairs()),
        ] + parent::tableFilters();
    }

    // public
    #[Url(except: self::TAB_ALL)]
    public string $tab = self::TAB_ALL;

    /**
     * Quick filters: all, unbound, fields of each mutable class and of each type of a class.
     *
     * @return array<string, array{label: string, group: string, groupLabel: ?string, query: ?Closure}>
     */
    public function getTabs(): array
    {
        if ($this->tabsCache !== null) {
            return $this->tabsCache;
        }

        $tabs = [
            self::TAB_ALL => ['label' => __('mutable-content-daisyui::ui.tabs.all'), 'group' => 'common', 'groupLabel' => null, 'query' => null],
            self::TAB_UNBOUND => ['label' => __('mutable-content-daisyui::ui.tabs.unbound'), 'group' => 'common', 'groupLabel' => null, 'query' => fn (Builder $query) => $query->whereDoesntHave('usages')],
        ];

        $mcds = app(MutableClassRegistry::class)->all();
        usort($mcds, fn ($a, $b) => mb_strtolower($a->label) <=> mb_strtolower($b->label));

        foreach ($mcds as $mcd) {
            $mutableClass = $mcd->mutableClass;

            $tabs[Str::slug(str_replace('\\', '-', $mutableClass))] = [
                'label' => $mcd->label,
                'group' => 'classes',
                'groupLabel' => __('mutable-content-daisyui::ui.tabs.classes'),
                'query' => fn (Builder $query) => static::boundTo($query, $mutableClass),
            ];
        }

        foreach ($mcds as $mcd) {
            $mutableClass = $mcd->mutableClass;

            $types = $mutableClass::getTypeOptions();
            uasort($types, fn ($a, $b) => mb_strtolower((string)$a) <=> mb_strtolower((string)$b));

            foreach ($types as $typeCode => $typeLabel) {
                $typeCode = (string)$typeCode;

                $tabs[self::TYPE_TAB_PREFIX.Str::slug(str_replace('\\', '-', $mutableClass)).'-'.Str::slug($typeCode)] = [
                    'label' => (string)$typeLabel,
                    'group' => 'type:'.$mutableClass,
                    'groupLabel' => $mcd->label,
                    'query' => fn (Builder $query) => static::boundToType($query, $mutableClass, $typeCode),
                ];
            }
        }

        return $this->tabsCache = $tabs;
    }

    public function tabCount(string $key): int
    {
        $query = FieldModel::query();

        $tab = $this->getTabs()[$key] ?? null;

        if ($tab && $tab['query']) {
            $tab['query']($query);
        }

        return $query->count();
    }

    public function setTab(string $key): void
    {
        $this->tab = array_key_exists($key, $this->getTabs()) ? $key : self::TAB_ALL;

        $this->resetPage();
    }

    public function updatedData(mixed $value, string $key): void
    {
        if ($key === Field::CODE_FIELD_TYPE) {
            data_set($this->data, FieldTypeSettingsInputs::DISPLAY_UNIT_STATE_PATH, FieldTypeSettingsInputs::defaultDisplayUnit($value));
        }
    }

    public function title(): string
    {
        return __('mutable-content-daisyui::ui.fields');
    }

    public function recordUrl(ModelWithFields $record): ?string
    {
        return route(MutableContentDaisyUi::ROUTE_FIELD_USAGE, ['field' => $record->code()]);
    }

    public function topView(): ?string
    {
        return 'mutable-content-daisyui::livewire.fields.quick-filters';
    }
}
