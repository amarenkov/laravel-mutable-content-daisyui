<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Lovs;

use Illuminate\Database\Eloquent\Model;

use Amarenkov\MutableContent\Contracts\MutableModel;
use Amarenkov\MutableContent\Domain\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Lov as LovModel;

use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;

class ManageLovs extends ManageRecords
{
    // static
    protected static string $model = LovModel::class;

    // protected
    protected function defaultSort(): ?string
    {
        return Field::COMMON_CODE_LABEL;
    }

    protected function inputs(): array
    {
        $inputs = parent::inputs();

        $inputs[Field::COMMON_CODE_CODE]
            ->disabled(fn (array $data, ?Model $record) => $record instanceof LovModel && ($record->isSystem() || $record->isUsedInFields()))
            ->helper(fn (array $data, ?Model $record) => $record instanceof LovModel && !$record->isSystem() && $record->isUsedInFields()
                ? $record->usedInFieldsMessage(key: 'code_used_in_fields')
                : null);

        return $inputs;
    }

    protected function deleteDeniedReason(Model&MutableModel $record): ?string
    {
        return $record instanceof LovModel && $record->isUsedInFields() ? $record->usedInFieldsMessage() : null;
    }

    // public
    public function title(): string
    {
        return __('mutable-content-daisyui::ui.lovs');
    }

    public function recordUrl(Model&MutableModel $record): ?string
    {
        return route(MutableContentDaisyUi::ROUTE_LOV_ITEMS, ['lov' => $record->code()]);
    }
}
