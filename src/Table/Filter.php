<?php

namespace Amarenkov\MutableContentDaisyUi\Table;

use Closure;

use Illuminate\Database\Eloquent\Builder;

/**
 * Table filter: a select of values or a yes/no choice.
 */
class Filter
{
    // const
    public const TYPE_SELECT = 'select';
    public const TYPE_TERNARY = 'ternary';

    public const TERNARY_TRUE = '1';
    public const TERNARY_FALSE = '0';

    // static
    /**
     * @param array<string|int, string>|Closure(): array<string|int, string> $options
     * @param Closure(Builder, string $value): mixed $query
     */
    public static function select(string $name, string $label, array|Closure $options, Closure $query): static
    {
        $filter = new static($name, self::TYPE_SELECT, $label, $query);
        $filter->options = $options;

        return $filter;
    }

    /**
     * @param Closure(Builder): mixed $true
     * @param Closure(Builder): mixed $false
     */
    public static function ternary(string $name, string $label, Closure $true, Closure $false): static
    {
        $filter = new static($name, self::TYPE_TERNARY, $label, function (Builder $query, string $value) use ($true, $false) {
            $value === self::TERNARY_TRUE ? $true($query) : $false($query);
        });

        $filter->options = fn () => [
            self::TERNARY_TRUE => $filter->trueLabel ?? __('mutable-content-daisyui::ui.yes'),
            self::TERNARY_FALSE => $filter->falseLabel ?? __('mutable-content-daisyui::ui.no'),
        ];

        return $filter;
    }

    // protected
    /** @var array<string|int, string>|Closure */
    protected array|Closure $options = [];

    protected ?string $trueLabel = null;
    protected ?string $falseLabel = null;

    // public
    final public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        protected Closure $query,
    )
    {
    }

    public function trueLabel(string $label): static
    {
        $this->trueLabel = $label;

        return $this;
    }

    public function falseLabel(string $label): static
    {
        $this->falseLabel = $label;

        return $this;
    }

    /**
     * @return array<string|int, string>
     */
    public function getOptions(): array
    {
        return $this->options instanceof Closure ? (($this->options)() ?? []) : $this->options;
    }

    public function apply(Builder $query, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        ($this->query)($query, (string)$value);
    }
}
