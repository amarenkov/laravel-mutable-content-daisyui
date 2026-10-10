<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Illuminate\Support\Facades\Blade;

use Livewire\Livewire;

use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageLovs;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_navigation_lists_the_management_screens(): void
    {
        $this->assertSame(
            [MutableContentDaisyUi::ROUTE_FIELDS, MutableContentDaisyUi::ROUTE_LOVS],
            array_column(MutableContentDaisyUi::navigation(), 'route')
        );

        $html = Blade::render('<x-mutable-content-daisyui::navigation />');

        $this->assertStringContainsString('System settings', $html);
        $this->assertStringContainsString(route(MutableContentDaisyUi::ROUTE_FIELDS), $html);
        $this->assertStringContainsString(route(MutableContentDaisyUi::ROUTE_LOVS), $html);
    }

    public function test_delete_confirmation_names_the_record(): void
    {
        $lov = new Lov();
        $lov->fill(['code' => 'priority', 'label' => 'Priority']);
        $lov->save();

        Livewire::test(ManageLovs::class)
            ->assertSeeHtml('wire:confirm="Delete &quot;Priority&quot;?"');
    }
}
