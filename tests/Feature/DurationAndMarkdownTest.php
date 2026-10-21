<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Illuminate\Support\Facades\Blade;

use Livewire\Livewire;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;
use Amarenkov\MutableContent\Domain\Field\TypeSettings;
use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Field\Usage;
use Amarenkov\MutableContent\Models\Lov\Item;
use Amarenkov\MutableContent\Models\Lov\Lov;
use Amarenkov\MutableContent\ValueObjects\Duration;

use Amarenkov\MutableContentDaisyUi\Form\FieldTypeSettingsInputs;
use Amarenkov\MutableContentDaisyUi\Helpers\MarkdownHelper;
use Amarenkov\MutableContentDaisyUi\Livewire\Fields\ManageFields;
use Amarenkov\MutableContentDaisyUi\Livewire\Lovs\ManageItems;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class DurationAndMarkdownTest extends TestCase
{
    protected function makeLovWithField(string $code, string $type, array $settings = []): Lov
    {
        $lov = new Lov();
        $lov->fill(['code' => 'job', 'label' => 'Job']);
        $lov->save();

        $field = new Field();
        $field->fill(array_filter(['code' => $code, 'label' => ucfirst($code), 'field_type' => $type, 'field_type_settings' => $settings ?: null]));
        $field->save();

        $usage = new Usage();
        $usage->fill([Usage::FIELD_FIELD_ID => $field->id, Usage::CODE_MUTABLE_CLASS => Item::class, Usage::CODE_TYPE_CODE => 'job']);
        $usage->save();

        return $lov;
    }

    public function test_duration_is_entered_and_shown_in_the_display_unit(): void
    {
        $lov = $this->makeLovWithField('estimate', Type::TYPE_DURATION, [TypeSettings::DISPLAY_UNIT => Duration::UNIT_HOUR]);

        $component = Livewire::test(ManageItems::class, ['lov' => 'job'])
            ->call('create')
            ->set('data.code', 'deploy')
            ->set('data.label', 'Deploy')
            ->set('data.estimate', '1.5')
            ->call('save')
            ->assertHasNoErrors();

        $item = Item::where('lov_id', $lov->id)->first();

        $this->assertSame(5400, $item->getField('estimate'));
        $this->assertSame('1.5 h', (string)$component->instance()->getColumns()['estimate']->render($item));
    }

    public function test_text_format_is_set_on_the_field(): void
    {
        Livewire::test(ManageFields::class)
            ->call('create')
            ->set('data.code', 'notes')
            ->set('data.label', 'Notes')
            ->set('data.field_type', Type::TYPE_TEXT)
            ->assertSee(__('mutable-content-daisyui::ui.settings.format'))
            ->set('data.'.FieldTypeSettingsInputs::FORMAT_STATE_PATH, TypeSettings::FORMAT_MARKDOWN)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals([TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN], Field::where('code', 'notes')->first()->getField('field_type_settings'));
        $this->assertSame(['Markdown'], FieldTypeSettingsInputs::describe(Type::TYPE_TEXT, [TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN]));
        $this->assertSame([], FieldTypeSettingsInputs::describe(Type::TYPE_TEXT, [TypeSettings::FORMAT => TypeSettings::FORMAT_PLAIN]));
    }

    public function test_markdown_column_shows_plain_text(): void
    {
        $lov = $this->makeLovWithField('notes', Type::TYPE_TEXT, [TypeSettings::FORMAT => TypeSettings::FORMAT_MARKDOWN]);

        $component = Livewire::test(ManageItems::class, ['lov' => 'job'])
            ->call('create')
            ->assertSee(__('mutable-content-daisyui::ui.markdown_helper'))
            ->set('data.code', 'deploy')
            ->set('data.label', 'Deploy')
            ->set('data.notes', "## Steps\n\n- **build**\n- ship")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Steps build ship', (string)$component->instance()->getColumns()['notes']->render(Item::where('lov_id', $lov->id)->first()));
    }

    public function test_markdown_is_rendered_safely(): void
    {
        $html = (string)MarkdownHelper::toHtml("# Title\n\n- [x] done\n- [ ] todo\n\n<script>alert(1)</script>\n\n[link](javascript:alert(1)) [site](https://example.com)");

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('checked', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('<a href="https://example.com">site</a>', $html);
        $this->assertSame('', MarkdownHelper::toText(null));

        $component = Blade::render('<x-mutable-content-daisyui::markdown text="**bold**" class="mt-2" />');

        $this->assertStringContainsString('<strong>bold</strong>', $component);
        $this->assertStringContainsString('mt-2', $component);
    }
}
