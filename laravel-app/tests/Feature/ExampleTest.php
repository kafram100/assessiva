<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic functional test example.
     */
    public function test_the_application_health_endpoint_returns_a_successful_response(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_practice_quizzes_page_renders_successfully(): void
    {
        $response = $this->get('/practice-quizzes');

        $response->assertStatus(200);
    }
}
