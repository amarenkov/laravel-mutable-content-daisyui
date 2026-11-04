<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Livewire\Livewire;

use Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Livewire\ManageNotes;
use Amarenkov\MutableContentDaisyUi\Tests\Fixtures\Models\Note;
use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class ModelOnTraitsTest extends TestCase
{
    public function test_model_without_change_log_and_soft_deletes_is_managed(): void
    {
        $component = Livewire::test(ManageNotes::class)
            ->call('create')
            ->set('data.body', 'First')
            ->call('save')
            ->assertHasNoErrors();

        $note = Note::query()->first();

        $this->assertSame('First', $note->body);

        $component->assertSeeText('First')
            ->call('edit', $note->getKey())
            ->assertSet('data.body', 'First')
            ->set('data.body', 'Second')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Second', $note->refresh()->body);

        $component->call('delete', $note->getKey());

        $this->assertSame(0, Note::query()->count());
    }
}
