<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Livewire;

use Amarenkov\MutableContent\Models\Field\Usage;

use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;

class ManageAllUsage extends ManageRecords
{
    protected static string $model = Usage::class;
}
