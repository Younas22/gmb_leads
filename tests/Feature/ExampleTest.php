<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The public home page renders on a freshly migrated database.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Http::preventStrayRequests();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
