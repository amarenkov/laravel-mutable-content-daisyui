<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class DateTimeTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('mutable-content.timezone', 'Europe/Moscow');
    }

    public function test_date_and_time_is_entered_and_shown_in_the_application_timezone(): void
    {
        $lov = new Lov();
        $lov->fill(['code' => 'event', 'label' => 'Event']);
        $lov->save();

        $field = new Field();
        $field->fill(['code' => 'happened_at', 'label' => 'Happened at', 'field_type' => Type::TYPE_DATETIME]);
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'event']);
        $usage->save();

        $component = Livewire::test(ManageItems::class, ['lov' => 'event'])
            ->call('create')
            ->set('data.code', 'launch')
            ->set('data.label', 'Launch')
            ->set('data.happened_at', '2026-10-10T12:00')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame('2026-10-10T09:00:00Z', $item->happened_at);

        $component->call('edit', $item->id)->assertSet('data.happened_at', '2026-10-10T12:00');

        $this->assertSame('10.10.2026 12:00', (string)$component->instance()->getColumns()['happened_at']->render($item));
    }
}
