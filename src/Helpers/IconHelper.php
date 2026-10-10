<?php

namespace Amarenkov\MutableContentDaisyUi\Helpers;

use ReflectionClass;

use Illuminate\Support\HtmlString;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;

/**
 * Icon field value: a Heroicon name, "o-cube" (outlined) or "cube" (solid), the same as in Filament.
 */
class IconHelper
{
    // const
    public const OPTIONS_LIMIT = 50;

    // static
    /** @var ?array<string, string> */
    protected static ?array $values = null;

    /**
     * Icon values mapped to blade-icons names.
     *
     * @return array<string, string>
     */
    protected static function map(): array
    {
        if (static::$values !== null) {
            return static::$values;
        }

        $result = [];

        $directory = static::svgDirectory();

        foreach (['o' => 'o-', 's' => ''] as $set => $prefix) {
            foreach (glob($directory.'/'.$set.'-*.svg') ?: [] as $file) {
                $name = substr(basename($file, '.svg'), 2);

                $result[$prefix.$name] = 'heroicon-'.$set.'-'.$name;
            }
        }

        ksort($result);

        return static::$values = $result;
    }

    protected static function svgDirectory(): string
    {
        $reflection = new ReflectionClass(BladeHeroiconsServiceProvider::class);

        return dirname($reflection->getFileName(), 2).'/resources/svg';
    }

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_keys(static::map());
    }

    public static function getName(mixed $value): ?string
    {
        return is_string($value) ? (static::map()[$value] ?? null) : null;
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
