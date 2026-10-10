@props([
    'model',
    'live' => false,
    'options' => [],
    'icons' => [],
    'placeholder' => null,
    'placeholderIcon' => null,
    'nullable' => true,
    'disabled' => false,
    'error' => false,
    'size' => 'md',
    'searchModel' => null,
    'searchFrom' => 8,
])

@php
    use Amarenkov\MutableContentDaisyUi\Helpers\IconHelper;

    $id = 'mc-cb-'.substr(md5($model), 0, 12);
    $withIcons = (bool)array_filter($icons) || $placeholderIcon !== null;
    $withSearch = $searchModel !== null || count($options) >= $searchFrom;
    $placeholder ??= __('mutable-content-daisyui::ui.select_placeholder');
@endphp

<div {{ $attributes->class(['w-full']) }}
     x-data="{
        open: false,
        ready: false,
        tick: 0,
        query: '',
        active: -1,
        model: @js($model),
        live: @js((bool)$live),
        server: @js($searchModel),
        placeholder: @js((string)$placeholder),
        get value() {
            const value = this.$wire.$get(this.model);
            return value === undefined || value === '' ? null : value;
        },
        set value(value) {
            this.$wire.$set(this.model, value, this.live);
        },
        all() {
            return this.$refs.list ? [...this.$refs.list.querySelectorAll('[data-option]')] : [];
        },
        options() {
            return this.all().filter(el => el.style.display !== 'none');
        },
        isActive(el) {
            return this.ready && this.options().indexOf(el) === this.active;
        },
        nothingFound() {
            return this.ready && this.query !== '' && !this.all().some(el => el.dataset.value !== '' && this.matches(el));
        },
        matches(el) {
            if (this.server || this.query === '') return true;
            return el.dataset.search.includes(this.query.toLowerCase());
        },
        display() {
            this.tick;
            const value = this.value;
                if (!this.ready || value === null) {
                const empty = this.ready ? this.all().find(el => el.dataset.value === '') : null;
                if (empty && empty.hasAttribute('data-icon')) return '<span class=&quot;opacity-60&quot;>' + empty.querySelector('[data-content]').innerHTML + '</span>';
                const span = document.createElement('span');
                span.className = 'text-base-content/50';
                span.textContent = this.placeholder;
                return span.outerHTML;
            }
            const option = this.all().find(el => el.dataset.value !== '' && el.dataset.value == value);
            if (option) return option.querySelector('[data-content]').innerHTML;
            const span = document.createElement('span');
            span.textContent = String(value);
            return span.outerHTML;
        },
        place() {
            const rect = this.$refs.button.getBoundingClientRect();
            const popup = this.$refs.popup;
            const below = window.innerHeight - rect.bottom;
            popup.style.left = rect.left + 'px';
            popup.style.width = Math.max(rect.width, 240) + 'px';
            if (below < 320 && rect.top > below) {
                popup.style.top = 'auto';
                popup.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                popup.style.bottom = 'auto';
                popup.style.top = (rect.bottom + 4) + 'px';
            }
        },
        show(query = '') {
            if (this.open || this.$refs.button.disabled) return;
            this.open = true;
            this.query = query;
            this.$refs.popup.showPopover();
            this.place();
            this.$nextTick(() => {
                const options = this.options();
                this.active = Math.max(0, options.findIndex(el => el.dataset.value == this.value));
                this.scroll();
                this.$refs.search ? this.$refs.search.focus() : this.$refs.list.focus();
            });
        },
        hide(focus = true) {
            if (!this.open) return;
            this.open = false;
            this.$refs.popup.hidePopover();
            if (focus) this.$refs.button.focus();
        },
        choose(el) {
            this.value = el.dataset.value === '' ? null : el.dataset.value;
            this.hide();
        },
        move(step) {
            const options = this.options();
            if (!options.length) return;
            this.active = (this.active + step + options.length) % options.length;
            this.scroll();
        },
        scroll() {
            this.options()[this.active]?.scrollIntoView({ block: 'nearest' });
        },
        enter() {
            const option = this.options()[this.active];
            if (option) this.choose(option);
        },
        search() {
            this.active = 0;
            if (this.server) this.$wire.set(this.server, this.query);
        },
        typed(event) {
            if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) return;
            event.preventDefault();
            if (this.open) {
                this.query += event.key;
                this.$refs.search?.focus();
                this.search();
                return;
            }
            this.show(event.key);
            if (this.$refs.search) this.$nextTick(() => this.search());
        }
     }"
     x-init="$nextTick(() => ready = true); Livewire.hook('morphed', ({ component }) => { if (component.id === $wire.$id) tick++ }); document.addEventListener('scroll', () => open && place(), true); window.addEventListener('resize', () => open && place())"
     x-on:click.outside="hide(false)">
    <button type="button" x-ref="button" id="{{ $id }}"
            role="combobox" aria-haspopup="listbox" aria-controls="{{ $id }}-list" :aria-expanded="open"
            @class(['select w-full text-left', 'select-sm' => $size === 'sm', 'select-error' => $error])
            @disabled($disabled)
            x-on:click="open ? hide() : show()"
            x-on:keydown.down.prevent="show()"
            x-on:keydown.up.prevent="show()"
            x-on:keydown.enter.prevent="show()"
            x-on:keydown.space.prevent="show()"
            x-on:keydown="typed($event)">
        <span class="min-w-0 truncate" x-html="display()"></span>
    </button>

    <div popover="manual" x-ref="popup" wire:ignore.self
         class="m-0 overflow-hidden rounded-box border border-base-300 bg-base-100 p-0 text-base-content shadow-lg"
         style="position: fixed; inset: auto;"
         x-on:keydown.escape.prevent.stop="hide()">
        <div class="flex max-h-80 flex-col">
        @if ($withSearch)
            <div class="border-b border-base-300 p-2">
                <label class="input input-sm w-full">
                    {{ svg('lucide-search', 'size-4 opacity-50') }}
                    <input type="search" x-ref="search" class="grow" autocomplete="off"
                           placeholder="{{ __('mutable-content-daisyui::ui.search') }}"
                           role="searchbox" aria-controls="{{ $id }}-list"
                           x-model="query"
                           x-on:input.debounce.300ms="search()"
                           x-on:keydown.down.prevent="move(1)"
                           x-on:keydown.up.prevent="move(-1)"
                           x-on:keydown.enter.prevent="enter()"
                           x-on:keydown.tab="hide(false)">
                </label>
            </div>
        @endif

        <ul x-ref="list" id="{{ $id }}-list" role="listbox" tabindex="-1" aria-labelledby="{{ $id }}"
            class="menu w-full flex-nowrap overflow-y-auto p-1 outline-none"
            x-on:keydown.down.prevent="move(1)"
            x-on:keydown.up.prevent="move(-1)"
            x-on:keydown.enter.prevent="enter()">
            @if ($nullable)
                <li data-option data-value="" @if ($placeholderIcon) data-icon @endif data-search="{{ mb_strtolower($placeholder) }}" x-show="matches($el)" role="option"
                    :aria-selected="value === null || value === ''">
                    <button type="button" tabindex="-1" class="text-base-content/60"
                            :class="isActive($el.parentElement) && 'menu-focus'"
                            x-on:click="choose($el.parentElement)" x-on:mousemove="active = options().indexOf($el.parentElement)">
                        <span data-content class="inline-flex min-w-0 items-center gap-2">
                            @if ($withIcons)
                                {{ IconHelper::render($placeholderIcon, 'size-5') ?? new \Illuminate\Support\HtmlString('<span class="inline-block size-5 shrink-0"></span>') }}
                            @endif
                            <span class="truncate">{{ $placeholder }}</span>
                        </span>
                    </button>
                </li>
            @endif
            @foreach ($options as $value => $label)
                <li wire:key="{{ $id }}-option-{{ $value }}" data-option data-value="{{ $value }}"
                    data-search="{{ mb_strtolower($label.' '.$value) }}" x-show="matches($el)" role="option"
                    :aria-selected="value == @js((string)$value)">
                    <button type="button" tabindex="-1"
                            :class="{ 'menu-active': value == @js((string)$value), 'menu-focus': isActive($el.parentElement) }"
                            x-on:click="choose($el.parentElement)" x-on:mousemove="active = options().indexOf($el.parentElement)">
                        <span data-content class="inline-flex min-w-0 items-center gap-2">
                            @if ($withIcons)
                                {{ IconHelper::render($icons[$value] ?? null, 'size-5') ?? new \Illuminate\Support\HtmlString('<span class="inline-block size-5 shrink-0"></span>') }}
                            @endif
                            <span class="truncate">{{ $label }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
            <li x-show="nothingFound()" class="px-3 py-2 text-sm text-base-content/60">{{ __('mutable-content-daisyui::ui.nothing_found') }}</li>
        </ul>
        </div>
    </div>
</div>
