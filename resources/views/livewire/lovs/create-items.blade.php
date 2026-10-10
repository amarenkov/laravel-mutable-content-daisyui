<x-mutable-content-daisyui::modal open="createItemsOpen" :title="__('mutable-content-daisyui::ui.create_items.heading')" width="max-w-2xl">
    <form wire:submit="createItems" class="flex flex-col gap-4">
        <x-mutable-content-daisyui::field :label="__('mutable-content-daisyui::ui.create_items.labels')" required
                                          :error="$errors->first('labels')"
                                          :helper="__('mutable-content-daisyui::ui.create_items.labels_helper')">
            <x-mutable-content-daisyui::textarea wire:model="labels" rows="15" :error="$errors->has('labels')" />
        </x-mutable-content-daisyui::field>

        <x-mutable-content-daisyui::modal-actions>
            <x-mutable-content-daisyui::button variant="ghost" x-on:click="open = false">{{ __('mutable-content-daisyui::ui.cancel') }}</x-mutable-content-daisyui::button>
            <x-mutable-content-daisyui::button type="submit" variant="primary">
                <x-mutable-content-daisyui::spinner wire:loading wire:target="createItems" />
                {{ __('mutable-content-daisyui::ui.create_items.submit') }}
            </x-mutable-content-daisyui::button>
        </x-mutable-content-daisyui::modal-actions>
    </form>
</x-mutable-content-daisyui::modal>
