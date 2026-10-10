<?php

namespace Amarenkov\MutableContentDaisyUi\Form;

use Closure;

use Illuminate\Database\Eloquent\Model;

/**
 * Form input bound to a path in the form data ("code" or "field_type_settings.display_unit").
 */
class Input
{
    // const
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_SELECT = 'select';
    public const TYPE_TOGGLE = 'toggle';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';

    // static
    public static function make(string $statePath, string $type = self::TYPE_TEXT): static
    {
        return new static($statePath, $type);
    }

    // protected
    protected ?string $label = null;

    protected string|Closure|null $helper = null;
    protected string|Closure|null $placeholder = null;
    protected string|Closure|null $placeholderIcon = null;
    protected ?string $suffix = null;

    /** @var array<string|int, string>|Closure|null */
    protected array|Closure|null $options = null;

    protected ?Closure $searchOptions = null;
    protected ?Closure $optionLabel = null;
    protected ?Closure $optionIcon = null;

    protected bool $selectablePlaceholder = true;

    protected bool $required = false;
    protected bool $live = false;
    protected bool $fullWidth = false;

    protected bool|Closure $disabled = false;
    protected bool|Closure $visible = true;

    /** @var array<mixed>|Closure */
    protected array|Closure $rules = [];

    protected ?int $maxLength = null;

    protected ?Closure $formatState = null;
    protected ?Closure $dehydrateState = null;

    // public
    final public function __construct(
        public readonly string $statePath,
        public string $type
    )
    {
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return (string)($this->label ?? $this->statePath);
    }

    /**
     * @param string|Closure(array $data, ?Model $record): ?string|null $helper
     */
    public function helper(string|Closure|null $helper): static
    {
        $this->helper = $helper;

        return $this;
    }

    public function getHelper(array $data, ?Model $record): ?string
    {
        return $this->helper instanceof Closure ? ($this->helper)($data, $record) : $this->helper;
    }

    /**
     * @param string|Closure(array $data, ?Model $record): ?string|null $placeholder
     */
    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(array $data, ?Model $record): ?string
    {
        return $this->placeholder instanceof Closure ? ($this->placeholder)($data, $record) : $this->placeholder;
    }

    /**
     * Icon shown with the placeholder of a select, e.g. the default icon.
     *
     * @param string|Closure(array $data, ?Model $record): ?string|null $placeholderIcon icon name
     */
    public function placeholderIcon(string|Closure|null $placeholderIcon): static
    {
        $this->placeholderIcon = $placeholderIcon;

        return $this;
    }

    public function getPlaceholderIcon(array $data, ?Model $record): ?string
    {
        return $this->placeholderIcon instanceof Closure ? ($this->placeholderIcon)($data, $record) : $this->placeholderIcon;
    }

    public function selectablePlaceholder(bool $value = true): static
    {
        $this->selectablePlaceholder = $value;

        return $this;
    }

    public function isPlaceholderSelectable(): bool
    {
        return $this->selectablePlaceholder;
    }

    public function suffix(?string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * @param array<string|int, string>|Closure(array $data, ?Model $record): array<string|int, string> $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Options found by a search string, for long lists; the select gets a search box.
     *
     * @param Closure(?string $search): array<string|int, string> $searchOptions
     * @param Closure(mixed $value): ?string $optionLabel label of the current value missing from the found options
     */
    public function searchable(Closure $searchOptions, Closure $optionLabel): static
    {
        $this->searchOptions = $searchOptions;
        $this->optionLabel = $optionLabel;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchOptions !== null;
    }

    /**
     * Icon of each option; the select then shows the options with icons.
     *
     * @param Closure(string|int $value): ?string $optionIcon icon name of the option
     */
    public function optionIcon(?Closure $optionIcon): static
    {
        $this->optionIcon = $optionIcon;

        return $this;
    }

    public function hasOptionIcons(): bool
    {
        return $this->optionIcon !== null;
    }

    public function getOptionIcon(string|int $value): ?string
    {
        return $this->optionIcon ? ($this->optionIcon)($value) : null;
    }

    /**
     * @return array<string|int, string>
     */
    public function getOptions(array $data, ?Model $record, ?string $search = null): array
    {
        if ($this->searchOptions) {
            $options = ($this->searchOptions)($search);
            $value = data_get($data, $this->statePath);

            if (filled($value) && !array_key_exists($value, $options)) {
                $options = [$value => ($this->optionLabel)($value) ?? (string)$value] + $options;
            }

            return $options;
        }

        if ($this->options instanceof Closure) {
            return ($this->options)($data, $record) ?? [];
        }

        return $this->options ?? [];
    }

    public function required(bool $value = true): static
    {
        $this->required = $value;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Send the value to the server on change, for inputs other inputs depend on.
     */
    public function live(bool $value = true): static
    {
        $this->live = $value;

        return $this;
    }

    public function isLive(): bool
    {
        return $this->live;
    }

    public function fullWidth(bool $value = true): static
    {
        $this->fullWidth = $value;

        return $this;
    }

    public function isFullWidth(): bool
    {
        return $this->fullWidth || $this->type === self::TYPE_TEXTAREA;
    }

    /**
     * @param bool|Closure(array $data, ?Model $record): bool $disabled
     */
    public function disabled(bool|Closure $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function isDisabled(array $data, ?Model $record): bool
    {
        return $this->disabled instanceof Closure ? (bool)($this->disabled)($data, $record) : $this->disabled;
    }

    /**
     * @param bool|Closure(array $data, ?Model $record): bool $visible
     */
    public function visible(bool|Closure $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isVisible(array $data, ?Model $record): bool
    {
        return $this->visible instanceof Closure ? (bool)($this->visible)($data, $record) : $this->visible;
    }

    /**
     * Rules added to the ones of the field definition.
     *
     * @param array<mixed>|Closure(array $data, ?Model $record): array<mixed> $rules
     */
    public function rules(array|Closure $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getRules(array $data, ?Model $record): array
    {
        return $this->rules instanceof Closure ? ($this->rules)($data, $record) : $this->rules;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * Form value from the record; by default the value at the state path.
     *
     * @param Closure(?Model $record): mixed $formatState
     */
    public function formatState(Closure $formatState): static
    {
        $this->formatState = $formatState;

        return $this;
    }

    /**
     * Value given to the model from the form value.
     *
     * @param Closure(mixed $state, array $data): mixed $dehydrateState
     */
    public function dehydrateState(Closure $dehydrateState): static
    {
        $this->dehydrateState = $dehydrateState;

        return $this;
    }

    public function getFormState(?Model $record): mixed
    {
        if ($this->formatState) {
            return ($this->formatState)($record);
        }

        if (!$record) {
            return $this->type === self::TYPE_TOGGLE ? false : null;
        }

        $value = data_get($record->getAttribute('fields'), $this->statePath);

        return $this->type === self::TYPE_TOGGLE ? (bool)$value : $value;
    }

    public function dehydrate(mixed $state, array $data): mixed
    {
        if ($state === '') {
            $state = null;
        }

        if ($this->type === self::TYPE_TOGGLE) {
            $state = (bool)$state;
        }

        return $this->dehydrateState ? ($this->dehydrateState)($state, $data) : $state;
    }
}
