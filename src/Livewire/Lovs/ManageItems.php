<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Lovs;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Item as LovItemModel;
use Amarenkov\MutableContent\Models\Lov\Lov as LovModel;

use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;

class ManageItems extends ManageRecords
{
    // static
    protected static string $model = LovItemModel::class;

    // protected
    protected function fieldScopes(): ?array
    {
        return LovItemModel::getFieldScopesForLov($this->lovRecord->code());
    }

    protected function parentRelation(): ?HasMany
    {
        return $this->lovRecord->items();
    }

    protected function defaultSort(): ?string
    {
        return Field::COMMON_CODE_LABEL;
    }

    // public
    public LovModel $lovRecord;

    public bool $createItemsOpen = false;

    public string $labels = '';

    public function mount(string $lov): void
    {
        $this->lovRecord = LovModel::query()->where('fields->'.Field::COMMON_CODE_CODE, $lov)->firstOrFail();
    }

    public function title(): string
    {
        return __('mutable-content-daisyui::ui.lov_items_title', ['lov' => $this->lovRecord->label() !== '' ? $this->lovRecord->label() : $this->lovRecord->code()]);
    }

    public function breadcrumbs(): array
    {
        return [
            route(MutableContentDaisyUi::ROUTE_LOVS) => __('mutable-content-daisyui::ui.lovs'),
            $this->lovRecord->label() !== '' ? $this->lovRecord->label() : $this->lovRecord->code(),
        ];
    }

    public function headerActions(): array
    {
        return [
            'openCreateItems' => __('mutable-content-daisyui::ui.create_items.label'),
        ];
    }

    public function extraView(): ?string
    {
        return 'mutable-content-daisyui::livewire.lovs.create-items';
    }

    public function openCreateItems(): void
    {
        $this->resetValidation();

        $this->labels = '';
        $this->createItemsOpen = true;
    }

    public function createItems(): void
    {
        $this->validate(
            ['labels' => ['required', 'string']],
            [],
            ['labels' => __('mutable-content-daisyui::ui.create_items.labels')]
        );

        $result = $this->lovRecord->createItemsFromLabels(preg_split('/\R/u', $this->labels), $this->logComment('create-items'), auth()->id());

        $created = count($result['created']);
        $restored = count($result['restored']);
        $skipped = count($result['skipped']);

        $body = [];

        if ($created) {
            $body[] = __('mutable-content-daisyui::ui.create_items.created', ['count' => $created]);
        }

        if ($restored) {
            $body[] = __('mutable-content-daisyui::ui.create_items.restored', ['count' => $restored]);
        }

        if ($skipped) {
            $body[] = __('mutable-content-daisyui::ui.create_items.skipped', ['count' => $skipped, 'labels' => implode(', ', $result['skipped'])]);
        }

        $isSomethingDone = $created || $restored;

        $this->notify(
            $isSomethingDone ? 'success' : 'warning',
            $isSomethingDone ? __('mutable-content-daisyui::ui.create_items.done') : __('mutable-content-daisyui::ui.create_items.nothing'),
            implode(' ', $body)
        );

        $this->createItemsOpen = false;
        $this->labels = '';
    }
}
