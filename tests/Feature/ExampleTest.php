<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->actingAs(User::query()->where('role', 'anggota')->first())->get('/');

        $response->assertStatus(200);
    }
}
