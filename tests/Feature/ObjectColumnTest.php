<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;

use Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Livewire\ManageAllUsage;
use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class ObjectColumnTest extends TestCase
{
    public function test_object_column_shows_object_titles(): void
    {
        $field = new Field();
        $field->fill(['code' => 'gears', 'field_type' => Type::TYPE_INT, 'label' => 'Number of gears']);
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class]);
        $usage->save();

        Livewire::test(ManageAllUsage::class)
            ->assertSeeText('Number of gears');
    }
}
