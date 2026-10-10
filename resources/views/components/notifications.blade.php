<div class="toast toast-end toast-bottom z-50"
     x-data="{ items: [], add(event) { const item = { id: Date.now() + Math.random(), ...event.detail }; this.items.push(item); setTimeout(() => this.remove(item.id), item.type === 'error' ? 8000 : 4000); }, remove(id) { this.items = this.items.filter(item => item.id !== id); } }"
     x-on:mutable-content-notify.window="add($event)">
    <template x-for="item in items" :key="item.id">
        <div role="alert" class="alert max-w-sm items-start shadow" :class="{ 'alert-success': item.type === 'success', 'alert-error': item.type === 'error', 'alert-warning': item.type === 'warning', 'alert-info': item.type === 'info' }">
            <div class="flex flex-col gap-1">
                <span class="font-semibold" x-text="item.title"></span>
                <span class="text-sm whitespace-normal" x-show="item.body" x-text="item.body"></span>
            </div>
            <button type="button" class="btn btn-ghost btn-xs btn-square" x-on:click="remove(item.id)">✕</button>
        </div>
    </template>
</div>
