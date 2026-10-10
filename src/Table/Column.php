<?php

namespace Amarenkov\MutableContentDaisyUi\Table;

use Closure;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use Amarenkov\MutableContent\Models\ModelWithFields;

/**
 * Table column: how a record value is shown, sorted and searched.
 */
class Column
{
    // static
    public static function make(string $name): static
    {
        return new static($name);
    }

    // protected
    protected ?string $label = null;

    protected ?Closure $state = null;
    protected ?Closure $format = null;
    protected ?Closure $prepare = null;

    protected ?Closure $sortUsing = null;
    protected ?Closure $searchUsing = null;

    protected bool $hiddenByDefault = false;
    protected bool $hiddenLabel = false;
    protected bool $numeric = false;

    // public
    final public function __construct(
        public readonly string $name
    )
    {
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return (string)($this->label ?? $this->name);
    }

    /**
     * @param Closure(ModelWithFields): mixed $state
     */
    public function state(Closure $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @param Closure(mixed $state, ModelWithFields $record): (string|Htmlable|null) $format
     */
    public function format(Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Called once with the records of the page before rendering, e.g. to load titles in one query.
     *
     * @param Closure(Collection<int, Model>): void $prepare
     */
    public function prepare(Closure $prepare): static
    {
        $this->prepare = $prepare;

        return $this;
    }

    /**
     * @param Closure(Builder, string $direction): mixed $sortUsing
     */
    public function sortable(Closure $sortUsing): static
    {
        $this->sortUsing = $sortUsing;

        return $this;
    }

    /**
     * @param Closure(Builder, string $search): mixed $searchUsing
     */
    public function searchable(Closure $searchUsing): static
    {
        $this->searchUsing = $searchUsing;

        return $this;
    }

    public function hiddenByDefault(bool $value = true): static
    {
        $this->hiddenByDefault = $value;

        return $this;
    }

    public function hiddenLabel(bool $value = true): static
    {
        $this->hiddenLabel = $value;

        return $this;
    }

    public function numeric(bool $value = true): static
    {
        $this->numeric = $value;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortUsing !== null;
    }

    public function isSearchable(): bool
    {
        return $this->searchUsing !== null;
    }

    public function isHiddenByDefault(): bool
    {
        return $this->hiddenByDefault;
    }

    public function isLabelHidden(): bool
    {
        return $this->hiddenLabel;
    }

    public function isNumeric(): bool
    {
        return $this->numeric;
    }

    public function applySort(Builder $query, string $direction): void
    {
        if ($this->sortUsing) {
            ($this->sortUsing)($query, $direction);
        }
    }

    public function applySearch(Builder $query, string $search): void
    {
        if ($this->searchUsing) {
            ($this->searchUsing)($query, $search);
        }
    }

    /**
     * @param Collection<int, Model> $records
     */
    public function prepareRecords(Collection $records): void
    {
        if ($this->prepare) {
            ($this->prepare)($records);
        }
    }

    public function getState(ModelWithFields $record): mixed
    {
        return $this->state ? ($this->state)($record) : $record->{$this->name};
    }

    public function render(ModelWithFields $record): string|Htmlable|null
    {
        $state = $this->getState($record);

        if ($this->format) {
            return ($this->format)($state, $record);
        }

        if ($state === null || is_scalar($state)) {
            return $state === null ? null : (string)$state;
        }

        return json_encode($state, JSON_UNESCAPED_UNICODE);
    }
}
