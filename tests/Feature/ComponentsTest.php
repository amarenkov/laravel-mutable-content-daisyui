<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Illuminate\Support\Facades\Blade;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class ComponentsTest extends TestCase
{
    public function test_segmented_is_a_radio_group_bound_to_the_model(): void
    {
        $html = Blade::render('<x-mutable-content-daisyui::segmented model="role" :options="[\'a\' => \'First\', \'b\' => \'Second\']" />');

        $this->assertStringContainsString('role="radiogroup"', $html);
        $this->assertSame(2, substr_count($html, 'wire:model.live="role"'));
        $this->assertStringContainsString('value="b" aria-label="Second"', $html);
        $this->assertSame(1, preg_match_all('/name="(mc-seg-[0-9a-f]+)"/', $html, $names) ? count(array_unique($names[1])) : 0);
    }

    public function test_status_variants(): void
    {
        $this->assertStringContainsString('class="status status-error animate-pulse"', Blade::render('<x-mutable-content-daisyui::status variant="error" pulse />'));
        $this->assertStringContainsString('class="status"', Blade::render('<x-mutable-content-daisyui::status />'));
    }

    public function test_card_has_a_body_only_when_asked(): void
    {
        $plain = Blade::render('<x-mutable-content-daisyui::card>Table</x-mutable-content-daisyui::card>');

        $this->assertStringNotContainsString('card-body', $plain);

        $this->blade('<x-mutable-content-daisyui::card title="Task"><x-slot:actions><button>Edit</button></x-slot:actions>
Text</x-mutable-content-daisyui::card>')
            ->assertSee('card-body', false)
            ->assertSee('>Task</h2>', false)
            ->assertSee('<button>Edit</button>', false)
            ->assertDontSee('@endslot', false);

        $this->assertStringContainsString('card-body', Blade::render('<x-mutable-content-daisyui::card body>Text</x-mutable-content-daisyui::card>'));
    }
}
