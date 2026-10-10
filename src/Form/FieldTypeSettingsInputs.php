<?php

namespace Amarenkov\MutableContentDaisyUi\Form;

use ArrayAccess;
use Closure;

use Illuminate\Database\Eloquent\Model;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Domain\Field\Lov\Type as FieldType;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;

use Amarenkov\MutableContent\Models\ModelWithFields;

/**
 * Field type settings inputs for the field and usage forms.
 */
class FieldTypeSettingsInputs
{
    // const
    public const DISPLAY_UNIT_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::DISPLAY_UNIT;
    public const ALLOW_UNLISTED_CODES_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::ALLOW_UNLISTED_CODES;
    public const ALLOW_ZERO_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::ALLOW_ZERO;
    public const LINK_BY_CODE_STATE_PATH = Field::CODE_FIELD_TYPE_SETTINGS.'.'.TypeSettings::LINK_BY_CODE;

    protected const FLAG_TRUE = '1';
    protected const FLAG_FALSE = '0';

    // static
    /**
     * @param Closure(array $data, ?Model $record): ?string $fieldType field type to show the settings for
     * @param ?Closure(): array $inherited overridden settings (the field ones for a usage); null if none
     *
     * @return array<string, Input>
     */
    public static function make(Closure $fieldType, ?Closure $inherited = null): array
    {
        return static::displayUnitInputs($fieldType, $inherited)
            + static::linkByCodeInputs($fieldType, $inherited)
            + static::allowUnlistedCodesInputs($fieldType, $inherited)
            + static::allowZeroInputs($fieldType, $inherited);
    }

    protected static function recordSetting(?Model $record, string $setting): mixed
    {
        if (!$record instanceof ModelWithFields) {
            return null;
        }

        $settings = $record->getField(Field::CODE_FIELD_TYPE_SETTINGS);

        return is_array($settings) || $settings instanceof ArrayAccess ? ($settings[$setting] ?? null) : null;
    }

    protected static function flagInput(string $setting, string $statePath, string $label, string|Closure $helper, Closure $visible, ?Closure $inherited): Input
    {
        if ($inherited) {
            return Input::make($statePath, Input::TYPE_SELECT)
                ->label($label)
                ->helper($helper)
                ->options([self::FLAG_TRUE => __('mutable-content-daisyui::ui.yes'), self::FLAG_FALSE => __('mutable-content-daisyui::ui.no')])
                ->placeholder(fn () => __('mutable-content-daisyui::ui.settings.inherited', [
                    'value' => ($inherited()[$setting] ?? null) === true ? __('mutable-content-daisyui::ui.yes_lower') : __('mutable-content-daisyui::ui.no_lower'),
                ]))
                ->formatState(fn (?Model $record) => match (static::recordSetting($record, $setting)) {
                    true => self::FLAG_TRUE,
                    false => self::FLAG_FALSE,
                    default => null,
                })
                ->dehydrateState(fn ($state) => match ((string)$state) {
                    self::FLAG_TRUE => true,
                    self::FLAG_FALSE => false,
                    default => null,
                })
                ->visible($visible);
        }

        return Input::make($statePath, Input::TYPE_TOGGLE)
            ->label($label)
            ->helper($helper)
            ->formatState(fn (?Model $record) => static::recordSetting($record, $setting) === true)
            ->dehydrateState(fn ($state) => $state ? true : null)
            ->visible($visible);
    }

    /**
     * @return array<string, Input>
     */
    protected static function allowZeroInputs(Closure $fieldType, ?Closure $inherited): array
    {
        $input = static::flagInput(
            TypeSettings::ALLOW_ZERO,
            self::ALLOW_ZERO_STATE_PATH,
            __('mutable-content-daisyui::ui.settings.allow_zero'),
            __('mutable-content-daisyui::ui.settings.allow_zero_helper'),
            fn (array $data, ?Model $record) => in_array((string)$fieldType($data, $record), TypeSettings::ALLOW_ZERO_TYPES, true),
            $inherited,
        );

        return [$input->statePath => $input];
    }

    /**
     * @return array<string, Input>
     */
    protected static function linkByCodeInputs(Closure $fieldType, ?Closure $inherited): array
    {
        $input = static::flagInput(
            TypeSettings::LINK_BY_CODE,
            self::LINK_BY_CODE_STATE_PATH,
            __('mutable-content-daisyui::ui.settings.link_by_code'),
            __('mutable-content-daisyui::ui.settings.link_by_code_helper'),
            fn (array $data, ?Model $record) => (string)$fieldType($data, $record) === FieldType::TYPE_OBJECT,
            $inherited,
        )->live();

        return [$input->statePath => $input];
    }

    protected static function linksByCodeInForm(array $data, ?Closure $inherited): bool
    {
        $state = data_get($data, self::LINK_BY_CODE_STATE_PATH);

        if ($state === null || $state === '') {
            return $inherited && ($inherited()[TypeSettings::LINK_BY_CODE] ?? null) === true;
        }

        return $state === true || (string)$state === self::FLAG_TRUE;
    }

    /**
     * @return array<string, Input>
     */
    protected static function allowUnlistedCodesInputs(Closure $fieldType, ?Closure $inherited): array
    {
        $input = static::flagInput(
            TypeSettings::ALLOW_UNLISTED_CODES,
            self::ALLOW_UNLISTED_CODES_STATE_PATH,
            __('mutable-content-daisyui::ui.settings.allow_unlisted_codes'),
            fn (array $data, ?Model $record) => (string)$fieldType($data, $record) === FieldType::TYPE_OBJECT
                ? __('mutable-content-daisyui::ui.settings.allow_unlisted_codes_object_helper')
                : __('mutable-content-daisyui::ui.settings.allow_unlisted_codes_helper'),
            fn (array $data, ?Model $record) => match ((string)$fieldType($data, $record)) {
                FieldType::TYPE_LOV_ITEM => true,
                FieldType::TYPE_OBJECT => static::linksByCodeInForm($data, $inherited),
                default => false,
            },
            $inherited,
        );

        return [$input->statePath => $input];
    }

    /**
     * @return array<string, Input>
     */
    protected static function displayUnitInputs(Closure $fieldType, ?Closure $inherited): array
    {
        $unitClass = fn (array $data, ?Model $record) => TypeSettings::getUnitClass((string)$fieldType($data, $record));

        $input = Input::make(self::DISPLAY_UNIT_STATE_PATH, Input::TYPE_SELECT)
            ->label(__('mutable-content-daisyui::ui.settings.display_unit'))
            ->options(function (array $data, ?Model $record) use ($unitClass) {
                $class = $unitClass($data, $record);

                return $class ? $class::unitOptions() : [];
            })
            ->helper(function (array $data, ?Model $record) use ($unitClass) {
                $class = $unitClass($data, $record);

                return $class ? __('mutable-content-daisyui::ui.settings.display_unit_helper', ['unit' => $class::unitLabel($class::baseUnit())]) : null;
            })
            ->visible(fn (array $data, ?Model $record) => $unitClass($data, $record) !== null);

        if ($inherited) {
            $input
                ->placeholder(function (array $data, ?Model $record) use ($inherited, $unitClass) {
                    $class = $unitClass($data, $record);

                    if (!$class) {
                        return null;
                    }

                    $unit = $inherited()[TypeSettings::DISPLAY_UNIT] ?? null;

                    return __('mutable-content-daisyui::ui.settings.inherited', ['value' => $class::unitLabel(in_array($unit, $class::units(), true) ? $unit : $class::baseUnit())]);
                })
                ->formatState(fn (?Model $record) => static::recordSetting($record, TypeSettings::DISPLAY_UNIT));

            return [$input->statePath => $input];
        }

        $input
            ->selectablePlaceholder(false)
            ->formatState(function (?Model $record) use ($unitClass) {
                $class = $unitClass([], $record);

                return static::recordSetting($record, TypeSettings::DISPLAY_UNIT) ?? ($class ? $class::baseUnit() : null);
            })
            ->dehydrateState(function ($state, array $data) use ($fieldType) {
                $class = TypeSettings::getUnitClass((string)$fieldType($data, null));

                return $class && $state !== $class::baseUnit() && in_array($state, $class::units(), true) ? $state : null;
            });

        return [$input->statePath => $input];
    }

    // public
    /**
     * Short description of the set type settings, for tables.
     *
     * @return array<string>
     */
    public static function describe(string $fieldType, mixed $settings): array
    {
        $result = [];

        $settings = is_array($settings) || $settings instanceof ArrayAccess ? $settings : [];

        $unitClass = TypeSettings::getUnitClass($fieldType);
        $unit = $settings[TypeSettings::DISPLAY_UNIT] ?? null;

        if ($unitClass && in_array($unit, $unitClass::units(), true)) {
            $result[] = __('mutable-content-daisyui::ui.settings.describe.display_unit', ['unit' => $unitClass::unitLabel($unit)]);
        }

        if ($fieldType === FieldType::TYPE_OBJECT) {
            $linkByCode = $settings[TypeSettings::LINK_BY_CODE] ?? null;

            if ($linkByCode === true) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.link_by_code');
            } elseif ($linkByCode === false) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.link_by_id');
            }
        }

        if (in_array($fieldType, [FieldType::TYPE_LOV_ITEM, FieldType::TYPE_OBJECT], true)) {
            $allowUnlisted = $settings[TypeSettings::ALLOW_UNLISTED_CODES] ?? null;

            if ($allowUnlisted === true) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.unlisted_codes');
            } elseif ($allowUnlisted === false) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.listed_codes_only');
            }
        }

        if (in_array($fieldType, TypeSettings::ALLOW_ZERO_TYPES, true)) {
            $allowZero = $settings[TypeSettings::ALLOW_ZERO] ?? null;

            if ($allowZero === true) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.allow_zero');
            } elseif ($allowZero === false) {
                $result[] = __('mutable-content-daisyui::ui.settings.describe.no_zero');
            }
        }

        return $result;
    }

    public static function defaultDisplayUnit(?string $fieldType): ?string
    {
        $class = TypeSettings::getUnitClass((string)$fieldType);

        return $class ? $class::baseUnit() : null;
    }
}
