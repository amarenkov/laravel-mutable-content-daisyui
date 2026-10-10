<?php

namespace Amarenkov\MutableContentDaisyUi\Livewire\Concerns;

/**
 * Path of the page the component was opened on, for log comments: later requests go to the Livewire endpoint.
 */
trait RemembersPath
{
    // public
    public string $path = '';

    public function mountRemembersPath(): void
    {
        $this->path = request()->path();
    }
}
