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
