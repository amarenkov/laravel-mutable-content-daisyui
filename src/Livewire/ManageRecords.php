<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as DomainFieldType;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Domain\LovRegistry;

use Amarenkov\MutableContent\Helpers\DatabaseHelper;
use Amarenkov\MutableContent\Helpers\ObjectHelper;
use Amarenkov\MutableContent\Helpers\RuleHelper;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\ValueObjects\Density;
use Amarenkov\MutableContent\ValueObjects\FieldValue;
use Amarenkov\MutableContent\ValueObjects\SurfaceDensity;

use Amarenkov\MutableContentDaisyUi\Exceptions\SaveFailed;
use Amarenkov\MutableContentDaisyUi\Form\Input;
use Amarenkov\MutableContentDaisyUi\Helpers\IconHelper;
use Amarenkov\MutableContentDaisyUi\Helpers\SaveHelper;
use Amarenkov\MutableContentDaisyUi\Livewire\Concerns\RemembersPath;
use Amarenkov\MutableContentDaisyUi\Table\Column;
use Amarenkov\MutableContentDaisyUi\Table\Filter;

/**
 * Records of a model with mutable fields: a table with search, sorting and filters, create and edit in a modal, delete.
 * Columns, filters and inputs are built from the field definitions.
 */
abstract class ManageRecords extends Component
{
    use RemembersPath;
    use WithPagination;

    // const
    public const DATE_DISPLAY_FORMAT = 'd.m.Y';

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected const UNLISTED_CODE_HINT = 'mutable-content-daisyui::ui.unlisted_code';

    // static
    /** @var class-string<ModelWithFields> */
    protected static string $model;

    // protected
    /** @var ?array<string, Field> */
    protected ?array $fieldsCache = null;

    /** @var ?array<string, Column> */
    protected ?array $columnsCache = null;

    /** @var ?array<string, Filter> */
    protected ?array $filtersCache = null;

    /** @var ?array<string, Input> */
    protected ?array $inputsCache = null;

    protected ?ModelWithFields $recordCache = null;

    /**
     * Field usage scopes of the page (see ModelWithFields::getFieldScopes()); null for the class ones.
     *
     * @return ?array<string>
     */
    protected function fieldScopes(): ?array
    {
        return null;
    }

    /**
     * @return array<string, Field>
     */
    protected function fields(): array
    {
        return $this->fieldsCache ??= static::$model::getFieldDefinitions($this->fieldScopes());
    }

    /**
     * Relation of the parent record the records belong to; null for top-level records.
     */
    protected function parentRelation(): ?HasMany
    {
        return null;
    }

    protected function parentModelClass(): ?string
    {
        return $this->parentRelation() ? get_class($this->parentRelation()->getParent()) : null;
    }

    protected function baseQuery(): Builder
    {
        return $this->parentRelation()?->getQuery() ?? static::$model::query();
    }

    protected function makeRecord(): ModelWithFields
    {
        return $this->ensureModelWithFields($this->parentRelation()?->make() ?? new (static::$model)());
    }

    protected function findRecord(int|string $id): ModelWithFields
    {
        return $this->ensureModelWithFields($this->baseQuery()->findOrFail($id));
    }

    protected function ensureModelWithFields(Model $record): ModelWithFields
    {
        if (!$record instanceof ModelWithFields) {
            throw new LogicException(static::class.' manages '.ModelWithFields::class.' records, got '.get_class($record));
        }

        return $record;
    }

    protected function getRecord(): ?ModelWithFields
    {
        if ($this->recordId === null) {
            return null;
        }

        if ($this->recordCache?->getKey() != $this->recordId) {
            $this->recordCache = $this->findRecord($this->recordId);
        }

        return $this->recordCache;
    }

    protected function defaultSort(): ?string
    {
        return null;
    }

    /**
     * Order of the records when no sortable column is chosen.
     */
    protected function applyDefaultOrder(Builder $query, string $direction): void
    {
    }

    protected function perPage(): int
    {
        return in_array($this->perPage, static::PER_PAGE_OPTIONS, true) ? $this->perPage : 50;
    }

    // columns section
    /**
     * @return array<string, Column>
     */
    protected function columns(): array
    {
        return $this->columnsFromFields();
    }

    /**
     * @return array<string, Column>
     */
    protected function columnsFromFields(): array
    {
        $fields = $this->fields();
        $parentModel = $this->parentModelClass();

        $systemMarkInCode = isset($fields[Field::COMMON_CODE_CODE], $fields[Field::COMMON_CODE_IS_SYSTEM]);

        $iconColumns = [];
        $columns = [];

        foreach ($fields as $field) {
            if ($field->fieldType === DomainFieldType::TYPE_SYSTEM) {
                continue;
            }

            if ($systemMarkInCode && $field->code === Field::COMMON_CODE_IS_SYSTEM) {
                continue;
            }

            if ($parentModel && $field->fieldType === DomainFieldType::TYPE_OBJECT && $field->objectClass === $parentModel) {
                continue;
            }

            $column = $this->columnFromField($field)->label($field->label);

            if (in_array($field->code, [Field::COMMON_CODE_CODE, Field::COMMON_CODE_LABEL], true)) {
                $this->sortableAndSearchable($column, $field->code);
            }

            if ($systemMarkInCode && $field->code === Field::COMMON_CODE_CODE) {
                $isSystemLabel = $fields[Field::COMMON_CODE_IS_SYSTEM]->label;

                $column->format(fn ($state, ModelWithFields $record) => static::textWithMarks((string)$state, $record->isSystem() ? [['lock', $isSystemLabel]] : []));
            }

            if ($field->fieldType === DomainFieldType::TYPE_ICON) {
                $iconColumns[$field->code] = $column->hiddenLabel();

                continue;
            }

            if ($field->fieldType === DomainFieldType::TYPE_TEXT) {
                $column->hiddenByDefault();
            }

            $columns[$field->code] = $column;
        }

        return $iconColumns + $columns;
    }

    protected function columnFromField(Field $field): Column
    {
        $lovRegistry = app(LovRegistry::class);

        $column = Column::make($field->code);

        switch ($field->fieldType) {
            case DomainFieldType::TYPE_BOOL:
                return $column->format(fn ($state) => $state ? static::icon('circle-check', 'size-5 text-success') : null);

            case DomainFieldType::TYPE_INT:
            case DomainFieldType::TYPE_FLOAT:
                return $column->numeric()->format(fn ($state) => is_numeric($state) ? static::formatNumber($state) : $state);

            case DomainFieldType::TYPE_TEXT:
                return $column->format(function ($state) {
                    if (!is_string($state) || mb_strlen($state) <= 50) {
                        return $state;
                    }

                    return new HtmlString('<span title="'.e($state).'">'.e(mb_substr($state, 0, 50)).'…</span>');
                });

            case DomainFieldType::TYPE_LOV:
                return $column->format(fn ($state) => filled($state) ? ($lovRegistry->getLovLabel($state) ?? $state) : null);

            case DomainFieldType::TYPE_LOV_ITEM:
                return $column->format(fn ($state) => $this->lovItemTitle($field, $state));

            case DomainFieldType::TYPE_OBJECT:
                return $this->objectColumn($column, $field);

            case DomainFieldType::TYPE_WEIGHT:
            case DomainFieldType::TYPE_LENGTH:
            case DomainFieldType::TYPE_AREA:
            case DomainFieldType::TYPE_VOLUME:
                return $column->numeric()->format(static::unitFormatter($field));

            case DomainFieldType::TYPE_DENSITY:
                return $column->numeric()->format(fn ($state) => static::formatValueObject(Density::class, $state));

            case DomainFieldType::TYPE_SURFACE_DENSITY:
                return $column->numeric()->format(fn ($state) => static::formatValueObject(SurfaceDensity::class, $state));

            case DomainFieldType::TYPE_DATE:
                return $column->format(fn ($state) => static::formatDate($state));

            case DomainFieldType::TYPE_ICON:
                return $column->format(fn ($state, ModelWithFields $record) => IconHelper::render($state, 'size-5', ObjectHelper::getTitle($record)));

            default:
                return $column;
        }
    }

    protected function objectColumn(Column $column, Field $field): Column
    {
        if (!$field->objectClass) {
            return $column->numeric();
        }

        $titles = [];

        if (TypeSettings::linksByCode($field)) {
            return $column
                ->prepare(function (Collection $records) use ($field, &$titles) {
                    $titles = ObjectHelper::getTitlesByCodes($field->objectClass, $records->pluck($field->code)->all());
                })
                ->format(fn ($state) => $this->objectCodeTitle($field, $state, $titles[(string)$state] ?? null));
        }

        return $column
            ->prepare(function (Collection $records) use ($field, &$titles) {
                $titles = ObjectHelper::getTitles($field->objectClass, $records->pluck($field->code)->all());
            })
            ->format(fn ($state) => $titles[ObjectHelper::toId($state)] ?? $state);
    }

    protected function sortableAndSearchable(Column $column, string $code): Column
    {
        return $column
            ->sortable(fn (Builder $query, string $direction) => $query->orderBy('fields->'.$code, $direction))
            ->searchable(fn (Builder $query, string $search) => DatabaseHelper::orWhereLike($query, 'fields->'.$code, '%'.addcslashes($search, '%_\\').'%'));
    }

    protected function lovItemTitle(Field $field, mixed $state): string|HtmlString|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        $lovRegistry = app(LovRegistry::class);

        if ($lovRegistry->hasLovItem($field->lovCode, $state)) {
            $label = (string)$lovRegistry->getLovItemLabel($field->lovCode, $state);

            if ($icon = IconHelper::render(IconHelper::lovItemIcon($field->lovCode, $state))) {
                return new HtmlString('<span class="inline-flex items-center gap-1.5">'.$icon->toHtml().e($label).'</span>');
            }

            return $label;
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return $lovRegistry->getLovItemLabel($field->lovCode, $state);
        }

        return static::textWithMarks((string)$state, [['triangle-alert', __(static::UNLISTED_CODE_HINT), 'text-error']]);
    }

    protected function objectCodeTitle(Field $field, mixed $state, ?string $title): string|HtmlString|null
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($title !== null) {
            return $title;
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return (string)$state;
        }

        return static::textWithMarks((string)$state, [['triangle-alert', __(static::UNLISTED_CODE_HINT), 'text-error']]);
    }

    // filters section
    /**
     * @return array<string, Filter>
     */
    protected function tableFilters(): array
    {
        return $this->filtersFromFields();
    }

    /**
     * @return array<string, Filter>
     */
    protected function filtersFromFields(): array
    {
        $fields = $this->fields();

        $filters = [];

        if (isset($fields[Field::COMMON_CODE_IS_SYSTEM])) {
            $filters[Field::COMMON_CODE_IS_SYSTEM] = $this->boolFilter($fields[Field::COMMON_CODE_IS_SYSTEM])
                ->trueLabel(__('mutable-content-daisyui::ui.filters.system_only'))
                ->falseLabel(__('mutable-content-daisyui::ui.filters.non_system_only'));
        }

        if ($unlistedCodesFilter = $this->unlistedCodesFilter($fields)) {
            $filters[$unlistedCodesFilter->name] = $unlistedCodesFilter;
        }

        return $filters;
    }

    protected function boolFilter(Field $field): Filter
    {
        $column = 'fields->'.$field->code;

        return Filter::ternary(
            $field->code,
            (string)$field->label,
            fn (Builder $query) => $query->where($column, true),
            fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull($column)->orWhere($column, false)),
        );
    }

    /**
     * @param Closure(): array<string|int, string> $options
     */
    protected function fieldsSelectFilter(string $code, string $label, Closure $options): Filter
    {
        return Filter::select($code, $label, $options, fn (Builder $query, string $value) => $query->where('fields->'.$code, $value));
    }

    /**
     * Records with an unlisted code in any field that allows such codes; null if there are no such fields.
     *
     * @param array<string, Field> $fields
     */
    protected function unlistedCodesFilter(array $fields): ?Filter
    {
        $codeFields = array_filter($fields, fn (Field $field) => in_array($field->fieldType, [DomainFieldType::TYPE_LOV_ITEM, DomainFieldType::TYPE_OBJECT], true) && TypeSettings::allowsUnlistedCodes($field));

        if (!$codeFields) {
            return null;
        }

        $hasUnlisted = function (Builder $query) use ($codeFields) {
            $lovRegistry = app(LovRegistry::class);

            foreach ($codeFields as $field) {
                $column = 'fields->'.$field->code;

                $codes = $field->fieldType === DomainFieldType::TYPE_OBJECT
                    ? ObjectHelper::codesQuery($field->objectClass)
                    : array_map('strval', array_keys($lovRegistry->getLovItemsOptions($field->lovCode)));

                $query->orWhere(fn (Builder $query) => $query->whereNotNull($column)->whereNotIn($column, $codes));
            }
        };

        return Filter::ternary(
            'unlisted_lov_codes',
            __('mutable-content-daisyui::ui.unlisted_codes_filter'),
            fn (Builder $query) => $query->where($hasUnlisted),
            fn (Builder $query) => $query->whereNot($hasUnlisted),
        )
            ->trueLabel(__('mutable-content-daisyui::ui.filters.with_them'))
            ->falseLabel(__('mutable-content-daisyui::ui.filters.without_them'));
    }

    // form section
    /**
     * @return array<string, Input>
     */
    protected function inputs(): array
    {
        return $this->inputsFromFields();
    }

    /**
     * @return array<string, Input>
     */
    protected function inputsFromFields(): array
    {
        $inputs = [];

        $maxLengths = static::$model::getFieldMaxLengths();

        foreach ($this->fields() as $field) {
            $usage = $field->usage();

            if ($usage->isImmutable || $field->fieldType === DomainFieldType::TYPE_SYSTEM) {
                continue;
            }

            $input = $this->inputFromField($field)->label($field->label);

            if (isset($maxLengths[$field->code]) && in_array($input->type, [Input::TYPE_TEXT, Input::TYPE_TEXTAREA], true)) {
                $input->maxLength($maxLengths[$field->code]);
            }

            if ($usage->isRequired) {
                $input->required();
            }

            if ($usage->isImmutableForSystemObjects) {
                $input->disabled(fn (array $data, ?Model $record) => $record instanceof ModelWithFields && $record->isSystem());
            }

            $inputs[$input->statePath] = $input;
        }

        return $inputs;
    }

    protected function inputFromField(Field $field): Input
    {
        $lovRegistry = app(LovRegistry::class);

        switch ($field->fieldType) {
            case DomainFieldType::TYPE_BOOL:
                return Input::make($field->code, Input::TYPE_TOGGLE);

            case DomainFieldType::TYPE_TEXT:
                return Input::make($field->code, Input::TYPE_TEXTAREA);

            case DomainFieldType::TYPE_INT:
                return Input::make($field->code, Input::TYPE_INTEGER);

            case DomainFieldType::TYPE_FLOAT:
                return Input::make($field->code, Input::TYPE_NUMBER);

            case DomainFieldType::TYPE_LOV:
                return Input::make($field->code, Input::TYPE_SELECT)->options(fn () => $lovRegistry->getLovsOptions() ?? []);

            case DomainFieldType::TYPE_LOV_ITEM:
                return $this->lovItemInput($field);

            case DomainFieldType::TYPE_OBJECT:
                return $this->objectInput($field);

            case DomainFieldType::TYPE_WEIGHT:
            case DomainFieldType::TYPE_LENGTH:
            case DomainFieldType::TYPE_AREA:
            case DomainFieldType::TYPE_VOLUME:
                return $this->unitInput($field);

            case DomainFieldType::TYPE_DENSITY:
                return Input::make($field->code, Input::TYPE_NUMBER)->suffix(__('mutable-content::units.kg_per_m3'));

            case DomainFieldType::TYPE_SURFACE_DENSITY:
                return Input::make($field->code, Input::TYPE_NUMBER)->suffix(__('mutable-content::units.kg_per_m2'));

            case DomainFieldType::TYPE_DATE:
                return Input::make($field->code, Input::TYPE_DATE);

            case DomainFieldType::TYPE_ICON:
                return Input::make($field->code, Input::TYPE_SELECT)
                    ->searchable(fn (?string $search) => IconHelper::getOptions($search), fn ($value) => is_string($value) ? $value : null)
                    ->optionIcon(fn ($value) => (string)$value)
                    ->rules([Rule::in(IconHelper::getValues())]);

            default:
                return Input::make($field->code);
        }
    }

    protected function lovItemInput(Field $field): Input
    {
        $lovRegistry = app(LovRegistry::class);

        $input = Input::make($field->code, Input::TYPE_SELECT);

        $itemCodes = array_keys($lovRegistry->getLovItemsOptions($field->lovCode) ?? []);

        foreach ($itemCodes as $code) {
            if (IconHelper::lovItemIcon($field->lovCode, $code) !== null) {
                $input->optionIcon(fn ($value) => IconHelper::lovItemIcon($field->lovCode, $value));

                break;
            }
        }

        if (!TypeSettings::allowsUnlistedCodes($field)) {
            return $input->options(fn () => $lovRegistry->getLovItemsOptions($field->lovCode) ?? []);
        }

        return $input->options(function (array $data) use ($field, $lovRegistry) {
            $options = $lovRegistry->getLovItemsOptions($field->lovCode) ?? [];
            $state = data_get($data, $field->code);

            if (filled($state) && !$lovRegistry->hasLovItem($field->lovCode, $state)) {
                $options = [$state => $state.' ('.__(static::UNLISTED_CODE_HINT).')'] + $options;
            }

            return $options;
        });
    }

    protected function objectInput(Field $field): Input
    {
        if (!$field->objectClass) {
            return Input::make($field->code, Input::TYPE_INTEGER);
        }

        $class = $field->objectClass;

        if (TypeSettings::linksByCode($field)) {
            return Input::make($field->code, Input::TYPE_SELECT)->searchable(
                fn (?string $search) => ObjectHelper::getOptions($class, $search, byCode: true),
                fn ($value) => ObjectHelper::getTitleByCode($class, $value) ?? (TypeSettings::allowsUnlistedCodes($field) ? $value.' ('.__(static::UNLISTED_CODE_HINT).')' : null),
            );
        }

        return Input::make($field->code, Input::TYPE_SELECT)
            ->searchable(
                fn (?string $search) => ObjectHelper::getOptions($class, $search),
                fn ($value) => ObjectHelper::getTitleById($class, $value),
            )
            ->dehydrateState(fn ($state) => ObjectHelper::toId($state));
    }

    protected static function displayUnit(Field $field): string
    {
        return TypeSettings::getValue($field, TypeSettings::DISPLAY_UNIT) ?? TypeSettings::getUnitClass($field->fieldType)::baseUnit();
    }

    protected function unitInput(Field $field): Input
    {
        $class = TypeSettings::getUnitClass($field->fieldType);
        $unit = static::displayUnit($field);

        $input = Input::make($field->code, Input::TYPE_NUMBER)->suffix($class::unitLabel($unit));

        if ($unit === $class::baseUnit()) {
            return $input;
        }

        return $input
            ->formatState(function (?Model $record) use ($class, $unit, $field) {
                $state = $record?->getAttribute('fields')[$field->code] ?? null;

                try {
                    return $class::fromFieldValue($state)?->toUnit($unit);
                } catch (InvalidArgumentException) {
                    return $state;
                }
            })
            ->dehydrateState(function ($state) use ($class, $unit) {
                if (!is_numeric($state)) {
                    return $state;
                }

                $value = $class::fromUnit($state, $unit);

                return $value instanceof FieldValue ? $value->toFieldValue() : $state;
            });
    }

    /**
     * @return array<string, Input>
     */
    protected function visibleInputs(?Model $record): array
    {
        return array_filter($this->getInputs(), fn (Input $input) => $input->isVisible($this->data, $record));
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function validationRules(?Model $record): array
    {
        $fieldRules = RuleHelper::getValidationRules(static::$model, null, $this->fieldScopes());

        $rules = [];

        foreach ($this->visibleInputs($record) as $path => $input) {
            if ($input->isDisabled($this->data, $record)) {
                continue;
            }

            $inputRules = $fieldRules[$path] ?? ['nullable'];

            if ($input->isRequired() && !in_array('required', $inputRules, true)) {
                $inputRules = array_values(array_diff($inputRules, ['nullable']));
                $inputRules[] = 'required';
            }

            $rules['data.'.$path] = [...$inputRules, ...$input->getRules($this->data, $record)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(?Model $record): array
    {
        $result = [];

        foreach ($this->visibleInputs($record) as $path => $input) {
            $result['data.'.$path] = $input->getLabel();
        }

        return $result;
    }

    /**
     * Data given to fill() of the record: values of visible enabled inputs.
     */
    protected function payload(?Model $record): array
    {
        $payload = [];

        foreach ($this->visibleInputs($record) as $path => $input) {
            if ($input->isDisabled($this->data, $record)) {
                continue;
            }

            data_set($payload, $path, $input->dehydrate(data_get($this->data, $path), $this->data));
        }

        return $payload;
    }

    protected function persist(ModelWithFields $record): void
    {
        $relation = $this->parentRelation();

        if ($relation && !$record->exists) {
            $relation->save($record);

            return;
        }

        $record->save();
    }

    protected function logComment(string $action): string
    {
        return trim($this->path.'/'.$action, '/');
    }

    // delete section
    public function canDelete(ModelWithFields $record): bool
    {
        return !$record->isSystem();
    }

    /**
     * Why the record cannot be deleted, shown to the user; null if it can.
     */
    protected function deleteDeniedReason(ModelWithFields $record): ?string
    {
        return null;
    }

    // rendering section
    public function title(): string
    {
        return ObjectHelper::getClassLabel(static::$model);
    }

    /**
     * @return array<string|int, string> url => label, the last one without url
     */
    public function breadcrumbs(): array
    {
        return [];
    }

    public function recordUrl(ModelWithFields $record): ?string
    {
        return null;
    }

    /**
     * Extra buttons in the page header: method name => label.
     *
     * @return array<string, string>
     */
    public function headerActions(): array
    {
        return [];
    }

    /**
     * Extra view rendered above the table, e.g. quick filters.
     */
    public function topView(): ?string
    {
        return null;
    }

    /**
     * Extra view rendered at the end of the page, e.g. a modal of a header action.
     */
    public function extraView(): ?string
    {
        return null;
    }

    public function formTitle(): string
    {
        return $this->recordId !== null ? __('mutable-content-daisyui::ui.edit') : __('mutable-content-daisyui::ui.create');
    }

    protected static function icon(string $value, string $class = 'size-4', ?string $title = null): ?HtmlString
    {
        return IconHelper::render($value, $class, $title);
    }

    /**
     * Text followed by icons with tooltips.
     *
     * @param array<array{0: string, 1: ?string, 2?: string}> $marks icon value, tooltip and a text color class
     */
    protected static function textWithMarks(string $text, array $marks): HtmlString
    {
        $html = e($text);

        foreach ($marks as $mark) {
            [$icon, $title] = $mark;

            $html .= ' '.static::icon($icon, 'size-4 align-text-bottom '.($mark[2] ?? 'text-base-content/50'), $title)?->toHtml();
        }

        return new HtmlString($html);
    }

    protected static function formatNumber(int|float|string $value): string
    {
        return rtrim(rtrim(number_format((float)$value, 6, ',', ' '), '0'), ',');
    }

    protected static function formatDate(mixed $state): ?string
    {
        if (!is_string($state) || $state === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $state);

        return $date ? $date->format(static::DATE_DISPLAY_FORMAT) : $state;
    }

    /**
     * @param class-string $class
     */
    protected static function formatValueObject(string $class, mixed $state): ?string
    {
        try {
            return $class::fromFieldValue($state)?->format();
        } catch (InvalidArgumentException) {
            return is_scalar($state) ? (string)$state : null;
        }
    }

    protected static function unitFormatter(Field $field): Closure
    {
        $class = TypeSettings::getUnitClass($field->fieldType);
        $unit = static::displayUnit($field);

        return function ($state) use ($class, $unit) {
            try {
                return $class::fromFieldValue($state)?->format(unit: $unit);
            } catch (InvalidArgumentException) {
                return is_scalar($state) ? (string)$state : null;
            }
        };
    }

    // state section
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $sort = '';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    #[Url(except: [])]
    public array $filters = [];

    #[Url(except: 50)]
    public int $perPage = 50;

    /** @var array<string> */
    public array $shownColumns = [];

    /** @var array<string> */
    public array $hiddenColumns = [];

    public array $data = [];

    /** @var array<string, string> */
    public array $optionSearch = [];

    public int|string|null $recordId = null;

    public bool $formOpen = false;

    // public
    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        return $this->columnsCache ??= $this->columns();
    }

    /**
     * @return array<string, Column>
     */
    public function getVisibleColumns(): array
    {
        return array_filter($this->getColumns(), fn (Column $column, string $name) => $this->isColumnShown($name, $column), ARRAY_FILTER_USE_BOTH);
    }

    public function isColumnShown(string $name, Column $column): bool
    {
        if (in_array($name, $this->hiddenColumns, true)) {
            return false;
        }

        return !$column->isHiddenByDefault() || in_array($name, $this->shownColumns, true);
    }

    public function toggleColumn(string $name): void
    {
        $column = $this->getColumns()[$name] ?? null;

        if (!$column) {
            return;
        }

        if ($this->isColumnShown($name, $column)) {
            $this->shownColumns = array_values(array_diff($this->shownColumns, [$name]));
            $this->hiddenColumns[] = $name;
        } else {
            $this->hiddenColumns = array_values(array_diff($this->hiddenColumns, [$name]));
            $this->shownColumns[] = $name;
        }
    }

    /**
     * @return array<string, Filter>
     */
    public function getFilters(): array
    {
        return $this->filtersCache ??= $this->tableFilters();
    }

    /**
     * @return array<string, Input>
     */
    public function getInputs(): array
    {
        return $this->inputsCache ??= $this->inputs();
    }

    public function hasSearch(): bool
    {
        foreach ($this->getColumns() as $column) {
            if ($column->isSearchable()) {
                return true;
            }
        }

        return false;
    }

    public function activeFiltersCount(): int
    {
        return count(array_filter(array_intersect_key($this->filters, $this->getFilters()), fn ($value) => $value !== null && $value !== ''));
    }

    public function query(): Builder
    {
        $query = $this->baseQuery();

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                foreach ($this->getColumns() as $column) {
                    $column->applySearch($query, $search);
                }
            });
        }

        foreach ($this->getFilters() as $name => $filter) {
            $filter->apply($query, $this->filters[$name] ?? null);
        }

        $sort = $this->sort !== '' ? $this->sort : $this->defaultSort();
        $direction = $this->direction === 'desc' ? 'desc' : 'asc';

        if ($sort !== null && ($column = $this->getColumns()[$sort] ?? null) && $column->isSortable()) {
            $column->applySort($query, $direction);
        } else {
            $this->applyDefaultOrder($query, $direction);
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    public function records(): LengthAwarePaginator
    {
        $records = $this->query()->paginate($this->perPage());

        foreach ($this->getVisibleColumns() as $column) {
            $column->prepareRecords($records->getCollection());
        }

        return $records;
    }

    public function sortBy(string $name): void
    {
        $column = $this->getColumns()[$name] ?? null;

        if (!$column || !$column->isSortable()) {
            return;
        }

        $current = $this->sort !== '' ? $this->sort : $this->defaultSort();

        if ($current === $name) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->direction = 'asc';
        }

        $this->sort = $name;

        $this->resetPage();
    }

    public function sortDirectionOf(string $name): ?string
    {
        $current = $this->sort !== '' ? $this->sort : $this->defaultSort();

        return $current === $name ? ($this->direction === 'desc' ? 'desc' : 'asc') : null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filters = [];

        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();

        $this->recordId = null;
        $this->recordCache = null;
        $this->optionSearch = [];
        $this->data = $this->formData(null);

        $this->formOpen = true;
    }

    public function edit(int|string $id): void
    {
        $this->resetValidation();

        $record = $this->findRecord($id);

        $this->recordId = $record->getKey();
        $this->recordCache = $record;
        $this->optionSearch = [];
        $this->data = $this->formData($record);

        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->recordId = null;
        $this->recordCache = null;
        $this->data = [];
    }

    /**
     * @return array<string, mixed>
     */
    public function formData(?ModelWithFields $record): array
    {
        $data = [];

        foreach ($this->getInputs() as $path => $input) {
            data_set($data, $path, $input->getFormState($record));
        }

        return $data;
    }

    public function save(): void
    {
        $record = $this->getRecord();

        $this->validate($this->validationRules($record), [], $this->validationAttributes($record));

        $isNew = $record === null;

        $record ??= $this->makeRecord();

        $record->fill($this->payload($record));
        $record->withLogContext($this->logComment($isNew ? 'create' : 'edit'))->user(auth()->id());

        try {
            SaveHelper::save(fn () => $this->persist($record));
        } catch (SaveFailed $exception) {
            $this->notify('error', $exception->title, $exception->body);

            return;
        }

        $this->closeForm();

        $this->notify('success', __('mutable-content-daisyui::ui.saved'));
    }

    public function delete(int|string $id): void
    {
        $record = $this->findRecord($id);

        if (!$this->canDelete($record)) {
            return;
        }

        if ($reason = $this->deleteDeniedReason($record)) {
            $this->notify('error', __('mutable-content-daisyui::ui.delete_denied'), $reason);

            return;
        }

        $record->withLogContext($this->logComment('destroy'))->user(auth()->id());

        try {
            SaveHelper::save(fn () => $record->delete());
        } catch (SaveFailed $exception) {
            $this->notify('error', $exception->title, $exception->body);

            return;
        }

        $this->notify('success', __('mutable-content-daisyui::ui.deleted'));
    }

    public function isInputDisabled(Input $input): bool
    {
        return $input->isDisabled($this->data, $this->formRecord());
    }

    public function formRecord(): ?ModelWithFields
    {
        return $this->getRecord();
    }

    public function notify(string $type, string $title, ?string $body = null): void
    {
        $this->dispatch('mutable-content-notify', type: $type, title: $title, body: $body);
    }

    public function renderCell(Column $column, ModelWithFields $record): string|Htmlable|null
    {
        return $column->render($record);
    }

    public function paginationView(): string
    {
        return 'mutable-content-daisyui::pagination';
    }

    public function render(): View
    {
        return view('mutable-content-daisyui::livewire.manage-records', [
            'records' => $this->records(),
            'columns' => $this->getVisibleColumns(),
            'formRecord' => $this->formOpen ? $this->formRecord() : null,
        ])
            ->layout(config('mutable-content-daisyui.layout'))
            ->title($this->title());
    }
}
