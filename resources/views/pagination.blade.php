@if ($paginator->hasPages())
    @php($pageName = $paginator->getPageName())
    <nav class="join" aria-label="{{ __('mutable-content-daisyui::ui.pagination') }}">
        <button type="button" class="join-item btn btn-sm" wire:click="previousPage('{{ $pageName }}')" @disabled($paginator->onFirstPage()) aria-label="{{ __('pagination.previous') }}">«</button>

        @foreach ($elements as $element)
            @if (is_string($element))
                <button type="button" class="join-item btn btn-sm btn-disabled">{{ $element }}</button>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <button type="button" wire:key="page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                            @class(['join-item btn btn-sm', 'btn-active' => $page == $paginator->currentPage()])>{{ $page }}</button>
                @endforeach
            @endif
        @endforeach

        <button type="button" class="join-item btn btn-sm" wire:click="nextPage('{{ $pageName }}')" @disabled(!$paginator->hasMorePages()) aria-label="{{ __('pagination.next') }}">»</button>
    </nav>
@endif
