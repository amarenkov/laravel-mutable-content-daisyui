<?php

namespace Amarenkov\MutableContentDaisyUi;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type as DomainFieldType;

use Amarenkov\MutableContentDaisyUi\Helpers\IconHelper;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageUsage;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageLovs;

class MutableContentDaisyUiServiceProvider extends ServiceProvider
{
    // protected
    /**
     * @return array<string, string>
     */
    protected function fieldTypeIcons(): array
    {
        return [
            DomainFieldType::TYPE_UNDEFINED => 'circle-help',
            DomainFieldType::TYPE_STRING => 'type',
            DomainFieldType::TYPE_TEXT => 'file-text',
            DomainFieldType::TYPE_BOOL => 'circle-check',
            DomainFieldType::TYPE_INT => 'hash',
            DomainFieldType::TYPE_FLOAT => 'calculator',
            DomainFieldType::TYPE_LOV => 'book-open',
            DomainFieldType::TYPE_LOV_ITEM => 'list',
            DomainFieldType::TYPE_ADDRESS => 'map-pin',
            DomainFieldType::TYPE_OBJECT => 'link',
            DomainFieldType::TYPE_WEIGHT => 'scale',
            DomainFieldType::TYPE_DENSITY => 'cuboid',
            DomainFieldType::TYPE_SURFACE_DENSITY => 'layers',
            DomainFieldType::TYPE_LENGTH => 'ruler',
            DomainFieldType::TYPE_AREA => 'square-dashed',
            DomainFieldType::TYPE_VOLUME => 'box',
            DomainFieldType::TYPE_DATE => 'calendar',
            DomainFieldType::TYPE_DATETIME => 'calendar-clock',
            DomainFieldType::TYPE_ICON => 'image',
            DomainFieldType::TYPE_SYSTEM => 'settings',
        ];
    }

    // public
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mutable-content-daisyui.php', 'mutable-content-daisyui');

        IconHelper::addLovItemIcons(DomainFieldType::CLASS_CODE, $this->fieldTypeIcons());
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mutable-content-daisyui');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'mutable-content-daisyui');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/mutable-content-daisyui'),
        ], 'mutable-content-daisyui-views');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-daisyui');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-daisyui'),
        ], 'mutable-content-daisyui-lang');

        $this->publishes([
            __DIR__.'/../config/mutable-content-daisyui.php' => config_path('mutable-content-daisyui.php'),
        ], 'mutable-content-daisyui-config');

        Livewire::component('mutable-content-daisyui.fields', ManageFields::class);
        Livewire::component('mutable-content-daisyui.field-usage', ManageUsage::class);
        Livewire::component('mutable-content-daisyui.lovs', ManageLovs::class);
        Livewire::component('mutable-content-daisyui.lov-items', ManageItems::class);
    }
}
