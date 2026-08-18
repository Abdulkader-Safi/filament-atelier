<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests assert on rendered HTML, never on the compiled asset bundle, so
     * they have no reason to need a Vite manifest. Without this, running the
     * suite anywhere the front end has not been built (a clean checkout, CI)
     * fails on a missing manifest rather than on anything real.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
