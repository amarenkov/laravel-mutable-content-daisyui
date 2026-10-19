<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class SecretTest extends TestCase
{
    protected function makeLovWithSecretField(bool $required = false, array $settings = []): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'service', 'label' => 'Service']);
        $lov->save();

        $field = new Field();
        $field->fill(array_filter(['code' => 'api_key', 'label' => 'API key', 'field_type' => Type::TYPE_SECRET, 'field_type_settings' => $settings ?: null]));
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'service', 'is_required' => $required]);
        $usage->save();

        return $lov;
    }

    public function test_secret_is_entered_masked_and_kept_when_left_empty(): void
    {
        $lov = $this->makeLovWithSecretField(required: true);

        $component = Livewire::test(ManageItems::class, ['lov' => 'service'])
            ->call('create')
            ->assertSeeHtml('type="password"')
            ->set('data.code', 'mail')
            ->set('data.label', 'Mail')
            ->call('save')
            ->assertHasErrors(['data.api_key' => 'required'])
            ->set('data.api_key', 'sk-123456789')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame('sk-123456789', $item->getSecret('api_key'));
        $this->assertSame('sk****89', (string)$component->instance()->getColumns()['api_key']->render($item));

        $component->call('edit', $item->id)
            ->assertSet('data.api_key', null)
            ->assertDontSeeHtml('sk-123456789')
            ->assertSeeHtml('placeholder="sk****89"')
            ->assertSee(__('mutable-content-daisyui::ui.secret_kept'))
            ->set('data.label', 'Mail service')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('sk-123456789', $item->fresh()->getSecret('api_key'));

        $component->call('edit', $item->id)
            ->set('data.api_key', 'sk-987654321')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('sk-987654321', $item->fresh()->getSecret('api_key'));
    }

    public function test_visible_characters_are_set_on_the_field(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'token')
            ->set('data.label', 'Token')
            ->set('data.field_type', Type::TYPE_SECRET)
            ->assertSee(__('mutable-content-daisyui::ui.settings.default', ['value' => TypeSettings::DEFAULT_VISIBLE]))
            ->set('data.'.FieldTypeSettingsInputs::VISIBLE_START_STATE_PATH, '4')
            ->set('data.'.FieldTypeSettingsInputs::VISIBLE_END_STATE_PATH, '0')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals([TypeSettings::VISIBLE_START => 4, TypeSettings::VISIBLE_END => 0], Field::where('code', 'token')->first()->getField('field_type_settings'));
        $this->assertSame(['4 characters shown at the start', '0 characters shown at the end'], FieldTypeSettingsInputs::describe(Type::TYPE_SECRET, [TypeSettings::VISIBLE_START => 4, TypeSettings::VISIBLE_END => 0]));

        $lov = $this->makeLovWithSecretField(settings: [TypeSettings::VISIBLE_START => 4, TypeSettings::VISIBLE_END => 0]);

        $component = Livewire::test(ManageItems::class, ['lov' => 'service'])
            ->call('create')
            ->set('data.code', 'mail')
            ->set('data.label', 'Mail')
            ->set('data.api_key', 'sk-123456789')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('sk-1****', (string)$component->instance()->getColumns()['api_key']->render(Item::where('lov_id', $lov->id)->first()));
    }
}
