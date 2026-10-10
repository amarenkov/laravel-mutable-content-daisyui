<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Helpers\IconHelper;

use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageUsage;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class FieldsTest extends TestCase
{
    protected function createField(string $code, string $type, array $attributes = []): Field
    {
        $field = new Field();
        $field->fill(['code' => $code, 'label' => ucfirst($code), 'field_type' => $type] + $attributes);
        $field->save();

        return $field;
    }

    public function test_field_is_created_and_bound_to_a_class(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'note')
            ->set('data.label', 'Note')
            ->set('data.field_type', Type::TYPE_STRING)
            ->call('save')
            ->assertHasNoErrors();

        $field = Field::where('fields->code', 'note')->first();

        $this->assertNotNull($field);
        $this->assertFalse($field->isSystem());

        Livewire::test(ManageUsage::class, ['field' => 'note'])
            ->call('create')
            ->set('data.'.Usage::CODE_MUTABLE_CLASS, Item::class)
            ->set('data.'.Usage::CODE_IS_REQUIRED, true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([Usage::makeScope(Usage::CODE_MUTABLE_CLASS, Item::class)], $field->usages()->pluck('scope')->all());
        $this->assertTrue(Item::getFieldDefinitions()['note']->usage()->isRequired);
    }

    public function test_invalid_field_code_is_rejected(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'Bad Code')
            ->set('data.label', 'Bad')
            ->set('data.field_type', Type::TYPE_STRING)
            ->call('save')
            ->assertHasErrors(['data.code']);

        $this->assertNull(Field::where('fields->label', 'Bad')->first());
    }

    public function test_system_field_cannot_be_deleted(): void
    {
        $field = Field::where('fields->code', 'code')->first();

        Livewire::test(ManageFields::class)->call('delete', $field->id);

        $this->assertNotNull(Field::find($field->id));
    }

    public function test_system_type_is_not_offered(): void
    {
        $component = Livewire::test(ManageFields::class)->call('create');

        $options = $component->instance()->getInputs()['field_type']->getOptions($component->get('data'), null);

        $this->assertArrayHasKey(Type::TYPE_STRING, $options);
        $this->assertArrayNotHasKey(Type::TYPE_SYSTEM, $options);
    }

    public function test_type_settings_follow_the_field_type(): void
    {
        $component = Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'weight')
            ->set('data.label', 'Weight')
            ->set('data.field_type', Type::TYPE_WEIGHT)
            ->assertSet('data.field_type_settings.display_unit', 'kg');

        $visible = array_keys(array_filter($component->instance()->getInputs(), fn ($input) => $input->isVisible($component->get('data'), null)));

        $this->assertContains('field_type_settings.display_unit', $visible);
        $this->assertContains('field_type_settings.allow_zero', $visible);
        $this->assertNotContains('field_type_settings.link_by_code', $visible);
        $this->assertNotContains('lov_code', $visible);

        $component
            ->set('data.field_type_settings.display_unit', 't')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['display_unit' => 't'], Field::where('fields->code', 'weight')->first()->getField('field_type_settings'));
    }

    public function test_base_display_unit_is_not_stored(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'weight')
            ->set('data.label', 'Weight')
            ->set('data.field_type', Type::TYPE_WEIGHT)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEmpty((array)(Field::where('fields->code', 'weight')->first()->getField('field_type_settings') ?? []));
    }

    public function test_quick_filters(): void
    {
        $this->createField('loose', Type::TYPE_STRING);

        $component = Livewire::test(ManageFields::class)->call('setTab', ManageFields::TAB_UNBOUND);

        $this->assertSame(['loose'], $component->instance()->records()->getCollection()->map->code()->all());

        $component->call('setTab', 'no-such-tab');

        $this->assertSame(ManageFields::TAB_ALL, $component->get('tab'));
    }

    public function test_usage_needs_a_class(): void
    {
        $this->createField('note', Type::TYPE_STRING);

        Livewire::test(ManageUsage::class, ['field' => 'note'])
            ->call('create')
            ->call('save')
            ->assertHasErrors(['data.'.Usage::CODE_MUTABLE_CLASS]);
    }

    public function test_usage_settings_inherit_the_field_ones(): void
    {
        $this->createField('weight', Type::TYPE_WEIGHT, ['field_type_settings' => ['display_unit' => 't']]);

        $component = Livewire::test(ManageUsage::class, ['field' => 'weight'])->call('create');

        $input = $component->instance()->getInputs()['field_type_settings.display_unit'];

        $this->assertStringContainsString('t', (string)$input->getPlaceholder($component->get('data'), null));

        $component
            ->set('data.'.Usage::CODE_MUTABLE_CLASS, Item::class)
            ->set('data.field_type_settings.allow_zero', '0')
            ->call('save')
            ->assertHasNoErrors();

        $usage = Usage::where('field_id', Field::where('fields->code', 'weight')->first()->id)->first();

        $this->assertNotNull($usage);
        $this->assertSame(['allow_zero' => false], $usage->getField('field_type_settings'));
    }

    public function test_usage_page_shows_the_field_title(): void
    {
        $this->createField('note', Type::TYPE_STRING);

        Livewire::test(ManageUsage::class, ['field' => 'note'])
            ->assertSee('Usage of the Note field');
    }

    public function test_field_type_icon_is_the_package_default_unless_set_in_the_admin_panel(): void
    {
        $this->assertSame('hash', IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));

        $lov = Lov::where('fields->code', Type::CLASS_CODE)->first();
        $item = $lov->items()->where('fields->code', Type::TYPE_INT)->first();

        $item->fill(['icon' => 'binary']);
        $item->save();

        $this->assertSame('binary', IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));

        $item->fill(['icon' => 'o-hashtag']);
        $item->save();

        $this->assertSame('hash', IconHelper::lovItemIcon(Type::CLASS_CODE, Type::TYPE_INT));
    }

    public function test_usage_is_bound_to_a_type_of_a_class(): void
    {
        $lov = new Lov();
        $lov->fill(['code' => 'vehicle', 'label' => 'Vehicle']);
        $lov->save();

        $this->createField('capacity', Type::TYPE_INT);

        $component = Livewire::test(ManageUsage::class, ['field' => 'capacity'])->call('create');

        $isTypeVisible = fn () => $component->instance()->getInputs()[Usage::CODE_TYPE_CODE]->isVisible($component->get('data'), null);

        $this->assertFalse($isTypeVisible());

        $component->set('data.'.Usage::CODE_MUTABLE_CLASS, Item::class);

        $this->assertTrue($isTypeVisible());
        $this->assertArrayHasKey('vehicle', $component->instance()->getInputs()[Usage::CODE_TYPE_CODE]->getOptions($component->get('data'), null));

        $component
            ->set('data.'.Usage::CODE_TYPE_CODE, 'vehicle')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([Item::getTypeScope('vehicle')], Field::where('fields->code', 'capacity')->first()->usages()->pluck('scope')->all());
        $this->assertTrue($lov->fresh()->isUsedInFields());
    }

    public function test_type_is_reset_when_the_class_changes(): void
    {
        $this->createField('capacity', Type::TYPE_INT);

        Livewire::test(ManageUsage::class, ['field' => 'capacity'])
            ->call('create')
            ->set('data.'.Usage::CODE_MUTABLE_CLASS, Item::class)
            ->set('data.'.Usage::CODE_TYPE_CODE, Type::CLASS_CODE)
            ->set('data.'.Usage::CODE_MUTABLE_CLASS, Lov::class)
            ->assertSet('data.'.Usage::CODE_TYPE_CODE, null);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->createField('capacity', Type::TYPE_INT);

        Livewire::test(ManageUsage::class, ['field' => 'capacity'])
            ->call('create')
            ->set('data.'.Usage::CODE_MUTABLE_CLASS, Item::class)
            ->set('data.'.Usage::CODE_TYPE_CODE, 'no_such_lov')
            ->call('save')
            ->assertHasErrors(['data.'.Usage::CODE_TYPE_CODE]);
    }

    public function test_quick_filters_have_types_of_typed_classes(): void
    {
        $tabs = Livewire::test(ManageFields::class)->instance()->getTabs();

        $typeTabs = array_filter($tabs, fn ($tab) => $tab['group'] === 'type:'.Item::class);

        $this->assertContains('Field type', array_column($typeTabs, 'label'));
    }
}
