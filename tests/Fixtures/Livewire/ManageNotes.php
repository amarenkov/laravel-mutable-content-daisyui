<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Livewire;

use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;

use Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Models\Note;

class ManageNotes extends ManageRecords
{
    protected static string $model = Note::class;
}
