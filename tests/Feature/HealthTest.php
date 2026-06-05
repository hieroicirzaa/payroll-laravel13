<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_homepage_loads(): void
    {
        $this->get('/')->assertOk();
    }
}
