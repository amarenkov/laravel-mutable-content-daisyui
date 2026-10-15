<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Currency;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class MoneyTest extends TestCase
{
    protected function makeLovWithMoneyField(array $settings = [], bool $required = false): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'product', 'label' => 'Product']);
        $lov->save();

        $field = new Field();
        $field->fill(array_filter(['code' => 'price', 'label' => 'Price', 'field_type' => Type::TYPE_MONEY, 'field_type_settings' => $settings ?: null]));
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'product', 'is_required' => $required]);
        $usage->save();

        return $lov;
    }

    protected function addCurrency(string $code, string $label, string $symbol): void
    {
        $item = new Item();
        $item->fill([Item::FIELD_LOV_ID => Currency::instance()->id, 'code' => $code, 'label' => $label, Currency::ITEM_FIELD_DECIMALS => 2, Currency::ITEM_FIELD_SYMBOL => $symbol]);
        $item->save();
    }

    public function test_amount_and_currency_are_entered_and_shown(): void
    {
        $this->addCurrency('BYN', 'Belarusian ruble', 'Br');

        $lov = $this->makeLovWithMoneyField();

        $component = Livewire::test(ManageItems::class, ['lov' => 'product'])
            ->call('create')
            ->assertSeeHtml('wire:model="data.price.amount"')
            ->assertSeeHtml('data.price.currency')
            ->assertSet('data.price.currency', null)
            ->set('data.code', 'tea')
            ->set('data.label', 'Tea')
            ->set('data.price.amount', '12.50')
            ->set('data.price.currency', 'BYN')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame(['amount' => 12.5, 'currency' => 'BYN'], $item->price);

        $component->call('edit', $item->id)
            ->assertSet('data.price.amount', '12.5')
            ->assertSet('data.price.currency', 'BYN');

        $this->assertSame("12.50\u{00A0}Br", (string)$component->instance()->getColumns()['price']->render($item));
    }

    public function test_empty_amount_of_an_optional_field_is_no_value(): void
    {
        $lov = $this->makeLovWithMoneyField();

        Livewire::test(ManageItems::class, ['lov' => 'product'])
            ->call('create')
            ->assertSet('data.price.currency', Currency::USD)
            ->set('data.code', 'tea')
            ->set('data.label', 'Tea')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Item::where('lov_id', $lov->id)->first()->price);
    }

    public function test_required_field_and_currency_decimals_are_validated(): void
    {
        $this->makeLovWithMoneyField(required: true);

        Livewire::test(ManageItems::class, ['lov' => 'product'])
            ->call('create')
            ->set('data.code', 'tea')
            ->set('data.label', 'Tea')
            ->call('save')
            ->assertHasErrors(['data.price' => 'required'])
            ->set('data.price.amount', '1.005')
            ->call('save')
            ->assertHasErrors(['data.price']);
    }

    public function test_fixed_currency_is_a_suffix_and_sign_settings_apply(): void
    {
        $this->addCurrency('BYN', 'Belarusian ruble', 'Br');

        $lov = $this->makeLovWithMoneyField([TypeSettings::CURRENCY => 'BYN', TypeSettings::ALLOW_NEGATIVE => false]);

        $component = Livewire::test(ManageItems::class, ['lov' => 'product'])
            ->call('create')
            ->assertSeeHtml('wire:model="data.price.amount"')
            ->assertDontSeeHtml('data.price.currency')
            ->assertSet('data.price.currency', 'BYN');

        $this->assertSame('Br', $component->instance()->getInputs()['price']->getSuffix());
        $this->assertSame([], $component->instance()->getInputs()['price']->getOptions([], null));

        $component
            ->set('data.code', 'tea')
            ->set('data.label', 'Tea')
            ->set('data.price.amount', '-1')
            ->call('save')
            ->assertHasErrors(['data.price'])
            ->set('data.price.amount', '3')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['amount' => 3, 'currency' => 'BYN'], Item::where('lov_id', $lov->id)->first()->price);
    }

    public function test_sign_and_currency_settings_in_the_field_form(): void
    {
        $component = Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'total')
            ->set('data.label', 'Total')
            ->set('data.field_type', Type::TYPE_MONEY);

        $visible = array_keys(array_filter($component->instance()->getInputs(), fn ($input) => $input->isVisible($component->get('data'), null)));

        $this->assertContains(FieldTypeSettingsInputs::CURRENCY_STATE_PATH, $visible);
        $this->assertContains(FieldTypeSettingsInputs::ALLOW_NEGATIVE_STATE_PATH, $visible);
        $this->assertContains(FieldTypeSettingsInputs::ALLOW_ZERO_STATE_PATH, $visible);

        $component
            ->set('data.'.FieldTypeSettingsInputs::ALLOW_NEGATIVE_STATE_PATH, true)
            ->set('data.'.FieldTypeSettingsInputs::ALLOW_ZERO_STATE_PATH, false)
            ->set('data.'.FieldTypeSettingsInputs::CURRENCY_STATE_PATH, Currency::USD)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals([TypeSettings::ALLOW_ZERO => false, TypeSettings::CURRENCY => Currency::USD], Field::where('code', 'total')->first()->getField('field_type_settings'));
        $this->assertSame(['currency USD', 'no 0'], FieldTypeSettingsInputs::describe(Type::TYPE_MONEY, [TypeSettings::ALLOW_ZERO => false, TypeSettings::CURRENCY => Currency::USD]));
    }

    public function test_new_money_field_keeps_the_sign_defaults(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'total')
            ->set('data.label', 'Total')
            ->set('data.field_type', Type::TYPE_MONEY)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(Field::where('code', 'total')->first()->getField('field_type_settings'));
    }

    public function test_decimals_setting_is_saved_validated_and_shown(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'unit_price')
            ->set('data.label', 'Unit price')
            ->set('data.field_type', Type::TYPE_MONEY)
            ->set('data.'.FieldTypeSettingsInputs::DECIMALS_STATE_PATH, '6')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([TypeSettings::DECIMALS => 6], Field::where('code', 'unit_price')->first()->getField('field_type_settings'));
        $this->assertSame(['6 digits after the point'], FieldTypeSettingsInputs::describe(Type::TYPE_MONEY, [TypeSettings::DECIMALS => 6]));

        Field::where('code', 'unit_price')->forceDelete();

        $lov = $this->makeLovWithMoneyField([TypeSettings::DECIMALS => 6]);

        $component = Livewire::test(ManageItems::class, ['lov' => 'product'])
            ->call('create')
            ->set('data.code', 'tea')
            ->set('data.label', 'Tea')
            ->set('data.price.amount', '0.0045201')
            ->set('data.price.currency', Currency::USD)
            ->call('save')
            ->assertHasErrors(['data.price'])
            ->set('data.price.amount', '0.00452')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame("0.004520\u{00A0}$", (string)$component->instance()->getColumns()['price']->render($item));
    }
}
