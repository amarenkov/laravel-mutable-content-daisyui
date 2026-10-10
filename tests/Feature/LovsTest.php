<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Illuminate\Support\Facades\DB;

use Livewire\Livewire;

use Amarenkov\MutableContent\Helpers\DatabaseHelper;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageLovs;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class LovsTest extends TestCase
{
    protected function createLov(string $code, string $label): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => $code, 'label' => $label]);
        $lov->save();

        return $lov;
    }

    public function test_lov_is_created_with_log_author(): void
    {
        Livewire::test(ManageLovs::class)
            ->call('create')
            ->set('data.code', 'priority')
            ->set('data.label', 'Priority')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formOpen', false)
            ->assertDispatched('mutable-content-notify', type: 'success');

        $lov = Lov::where('fields->code', 'priority')->first();

        $this->assertNotNull($lov);
        $this->assertSame('Priority', $lov->label());

        $entry = DB::table(DatabaseHelper::logsTableName('lovs'))->where('entity_id', $lov->id)->first();

        $this->assertSame($this->user->id, (int)$entry->user_id);
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::test(ManageLovs::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors(['data.code' => 'required', 'data.label' => 'required'])
            ->assertSet('formOpen', true);
    }

    public function test_duplicate_code_is_reported_without_closing_the_form(): void
    {
        $this->createLov('priority', 'Priority');

        Livewire::test(ManageLovs::class)
            ->call('create')
            ->set('data.code', 'priority')
            ->set('data.label', 'Another')
            ->call('save')
            ->assertSet('formOpen', true)
            ->assertDispatched('mutable-content-notify', type: 'error');

        $this->assertSame(1, Lov::where('fields->code', 'priority')->count());
    }

    public function test_lov_is_edited(): void
    {
        $lov = $this->createLov('priority', 'Priority');

        Livewire::test(ManageLovs::class)
            ->call('edit', $lov->id)
            ->assertSet('data.label', 'Priority')
            ->set('data.label', 'Task priority')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Task priority', $lov->fresh()->label());
    }

    public function test_search_and_sort(): void
    {
        $this->createLov('alpha', 'Alpha');
        $this->createLov('omega', 'Omega');

        $component = Livewire::test(ManageLovs::class)->set('search', 'omeg');

        $this->assertSame(['omega'], $component->instance()->records()->getCollection()->map->code()->all());

        $component->set('search', '')->call('sortBy', 'code');

        $this->assertSame('alpha', $component->instance()->records()->first()->code());

        $component->call('sortBy', 'code');

        $this->assertSame('omega', $component->instance()->records()->first()->code());
    }

    public function test_system_lov_cannot_be_deleted(): void
    {
        $lov = Lov::where('fields->code', 'field_type')->first();

        Livewire::test(ManageLovs::class)->call('delete', $lov->id);

        $this->assertNotNull(Lov::find($lov->id));
    }

    public function test_lov_used_in_fields_cannot_be_deleted(): void
    {
        $lov = $this->createLov('priority', 'Priority');

        $field = new Field();
        $field->fill(['code' => 'priority', 'label' => 'Priority', 'field_type' => 'lov_item', 'lov_code' => 'priority']);
        $field->save();

        Livewire::test(ManageLovs::class)
            ->call('delete', $lov->id)
            ->assertDispatched('mutable-content-notify', type: 'error');

        $this->assertNotNull(Lov::find($lov->id));
    }

    public function test_items_page_shows_items_of_the_lov(): void
    {
        $lov = $this->createLov('priority', 'Priority');
        $lov->createItemsFromLabels(['High', 'Low']);

        Livewire::test(ManageItems::class, ['lov' => 'priority'])
            ->assertSee('Items of the Priority LOV')
            ->assertSee('High')
            ->assertSee('Low')
            ->assertDontSee('Integer');
    }

    public function test_item_is_created_in_the_lov(): void
    {
        $lov = $this->createLov('priority', 'Priority');

        Livewire::test(ManageItems::class, ['lov' => 'priority'])
            ->call('create')
            ->set('data.code', 'high')
            ->set('data.label', 'High')
            ->set('data.icon', 'flame')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame('high', $item->code());
        $this->assertSame('flame', $item->icon);
    }

    public function test_unknown_icon_is_rejected(): void
    {
        $this->createLov('priority', 'Priority');

        Livewire::test(ManageItems::class, ['lov' => 'priority'])
            ->call('create')
            ->set('data.code', 'high')
            ->set('data.label', 'High')
            ->set('data.icon', 'no-such-icon')
            ->call('save')
            ->assertHasErrors(['data.icon']);
    }

    public function test_items_are_added_as_a_list(): void
    {
        $lov = $this->createLov('priority', 'Priority');

        Livewire::test(ManageItems::class, ['lov' => 'priority'])
            ->call('openCreateItems')
            ->set('labels', "High\nLow\nHigh")
            ->call('createItems')
            ->assertSet('createItemsOpen', false)
            ->assertDispatched('mutable-content-notify', type: 'success');

        $this->assertEqualsCanonicalizing(['High', 'Low'], Item::where('lov_id', $lov->id)->get()->map->label()->all());
    }

    public function test_items_show_default_icons_of_the_package(): void
    {
        $component = Livewire::test(ManageItems::class, ['lov' => 'field_type']);

        $item = Item::where('fields->code', 'int')->first();

        $this->assertSame('hash', $component->instance()->getColumns()['icon']->getState($item));

        $input = $component->instance()->getInputs()['icon'];

        $this->assertSame('Default: hash', $input->getPlaceholder([], $item));
    }
}
