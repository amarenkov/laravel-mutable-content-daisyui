<?php

namespace Amarenkov\MutableContentDaisyUi;

use Illuminate\Support\Facades\Route;

use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageUsage;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageLovs;

class MutableContentDaisyUi
{
    // const
    public const ROUTE_FIELDS = 'mutable-content.fields';
    public const ROUTE_FIELD_USAGE = 'mutable-content.field-usage';
    public const ROUTE_LOVS = 'mutable-content.lovs';
    public const ROUTE_LOV_ITEMS = 'mutable-content.lov-items';

    // static
    /**
     * Menu items of the management screens: label, route name, route names the item is active on, Lucide icon.
     *
     * @return array<array{label: string, route: string, active: array<string>, icon: string}>
     */
    public static function navigation(): array
    {
        return [
            [
                'label' => __('mutable-content-daisyui::ui.fields'),
                'route' => self::ROUTE_FIELDS,
                'active' => [self::ROUTE_FIELDS, self::ROUTE_FIELD_USAGE],
                'icon' => 'layout-grid',
            ],
            [
                'label' => __('mutable-content-daisyui::ui.lovs'),
                'route' => self::ROUTE_LOVS,
                'active' => [self::ROUTE_LOVS, self::ROUTE_LOV_ITEMS],
                'icon' => 'clipboard-list',
            ],
        ];
    }

    /**
     * Management screens; call inside the application route group with its middleware and prefix, without a name prefix.
     */
    public static function routes(): void
    {
        Route::get('fields', ManageFields::class)->name(self::ROUTE_FIELDS);
        Route::get('fields/{field}/usage', ManageUsage::class)->name(self::ROUTE_FIELD_USAGE);
        Route::get('lovs', ManageLovs::class)->name(self::ROUTE_LOVS);
        Route::get('lovs/{lov}/items', ManageItems::class)->name(self::ROUTE_LOV_ITEMS);
    }
}
