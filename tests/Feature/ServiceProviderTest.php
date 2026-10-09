<?php

namespace Amarenkov\MutableContentDaisyUi\Tests\Feature;

use Illuminate\Support\Facades\View;

use Amarenkov\MutableContentDaisyUi\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testViewsNamespaceIsRegistered(): void
    {
        $this->assertArrayHasKey('mutable-content-daisyui', View::getFinder()->getHints());
    }

    public function testTranslationsNamespaceIsRegistered(): void
    {
        $this->assertArrayHasKey('mutable-content-daisyui', app('translator')->getLoader()->namespaces());
    }
}
