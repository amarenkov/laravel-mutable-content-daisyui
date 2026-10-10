<?php

namespace Amarenkov\MutableContentDaisyUi;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type as DomainFieldType;
use Amarenkov\MutableContent\Domain\LovRegistry;

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
            DomainFieldType::TYPE_UNDEFINED => 'o-question-mark-circle',
            DomainFieldType::TYPE_STRING => 'o-pencil',
            DomainFieldType::TYPE_TEXT => 'o-document-text',
            DomainFieldType::TYPE_BOOL => 'o-check-circle',
            DomainFieldType::TYPE_INT => 'o-hashtag',
            DomainFieldType::TYPE_FLOAT => 'o-calculator',
            DomainFieldType::TYPE_LOV => 'o-book-open',
            DomainFieldType::TYPE_LOV_ITEM => 'o-list-bullet',
            DomainFieldType::TYPE_ADDRESS => 'o-map-pin',
            DomainFieldType::TYPE_OBJECT => 'o-link',
            DomainFieldType::TYPE_WEIGHT => 'o-scale',
            DomainFieldType::TYPE_DENSITY => 'o-cube',
            DomainFieldType::TYPE_SURFACE_DENSITY => 'o-square-3-stack-3d',
            DomainFieldType::TYPE_LENGTH => 'o-arrows-right-left',
            DomainFieldType::TYPE_AREA => 'o-square-2-stack',
            DomainFieldType::TYPE_VOLUME => 'o-cube-transparent',
            DomainFieldType::TYPE_DATE => 'o-calendar',
            DomainFieldType::TYPE_ICON => 'o-photo',
            DomainFieldType::TYPE_SYSTEM => 'o-cog-6-tooth',
        ];
    }

    // public
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mutable-content-daisyui.php', 'mutable-content-daisyui');

        $this->callAfterResolving(LovRegistry::class, function (LovRegistry $lovRegistry) {
            $lovRegistry->addItemIcons(DomainFieldType::CLASS_CODE, $this->fieldTypeIcons());
        });
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

        Livewire::component('mutable-content-daisyui.lovs', ManageLovs::class);
        Livewire::component('mutable-content-daisyui.lov-items', ManageItems::class);
    }
}
