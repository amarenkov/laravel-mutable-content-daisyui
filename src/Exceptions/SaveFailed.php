<?php

namespace Amarenkov\MutableContentDaisyUi\Exceptions;

use RuntimeException;

class SaveFailed extends RuntimeException
{
    // public
    public function __construct(
        public readonly string $title,
        public readonly ?string $body = null
    )
    {
        parent::__construct($body ?? $title);
    }
}
