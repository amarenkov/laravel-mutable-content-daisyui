<?php

namespace Amarenkov\MutableContentDaisyUi\Helpers;

use Illuminate\Support\HtmlString;

use BladeUI\Icons\Factory as IconFactory;

use Amarenkov\MutableContent\Domain\LovRegistry;

/**
 * Icons of the package: Lucide (https://lucide.dev). An icon field value is a Lucide icon name, e.g. "flame".
 */
class IconHelper
{
    // const
    public const OPTIONS_LIMIT = 50;

    public const SET = 'lucide';

    // static
    /** @var ?array<string> */
    protected static ?array $values = null;

    /** @var array<string, array<string|int, string>> */
    protected static array $lovItemIcons = [];

    protected static function svgDirectory(): string
    {
        $paths = app(IconFactory::class)->all()[static::SET]['paths'] ?? [];

        return (string)($paths[0] ?? '');
    }

    /**
     * Default icons of LOV items, shown when the item has no valid icon of its own (from the code or the admin panel).
     *
     * @param array<string|int, string> $icons item code => icon name
     */
    public static function addLovItemIcons(string $lovCode, array $icons): void
    {
        static::$lovItemIcons[$lovCode] = $icons + (static::$lovItemIcons[$lovCode] ?? []);
    }

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        if (static::$values !== null) {
            return static::$values;
        }

        $values = array_map(fn (string $file) => basename($file, '.svg'), glob(static::svgDirectory().'/*.svg') ?: []);

        sort($values);

        return static::$values = $values;
    }

    public static function isValid(mixed $value): bool
    {
        return is_string($value) && $value !== '' && in_array($value, static::getValues(), true);
    }

    public static function getName(mixed $value): ?string
    {
        return static::isValid($value) ? static::SET.'-'.$value : null;
    }

    /**
     * Icon of a LOV item: its own one if it is a valid icon of the package, the package default otherwise.
     */
    public static function lovItemIcon(string $lovCode, mixed $code): ?string
    {
        $icon = app(LovRegistry::class)->getLovItemIcon($lovCode, $code);

        if (static::isValid($icon)) {
            return $icon;
        }

        return static::lovItemDefaultIcon($lovCode, $code);
    }

    /**
     * Default icon of a LOV item given by the package or the application.
     */
    public static function lovItemDefaultIcon(string $lovCode, mixed $code): ?string
    {
        return is_string($code) || is_int($code) ? (static::$lovItemIcons[$lovCode][$code] ?? null) : null;
    }

    public static function render(mixed $value, string $class = 'size-4', ?string $title = null): ?HtmlString
    {
        $name = static::getName($value);

        if (!$name) {
            return null;
        }

        $attributes = $title !== null ? ['title' => $title] : [];

        return new HtmlString(svg($name, 'inline-block shrink-0 '.$class, $attributes)->toHtml());
    }

    /**
     * @return array<string, string>
     */
    public static function getOptions(?string $search = null, int $limit = self::OPTIONS_LIMIT): array
    {
        $search = mb_strtolower(trim((string)$search));

        $result = [];

        foreach (static::getValues() as $value) {
            if ($search !== '' && !str_contains($value, $search)) {
                continue;
            }

            $result[$value] = $value;

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }
}
