<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumIssue268Test extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function assert_credentials_using_spa()
    {
        // Create the admin
        $admin = User::factory()->create();

        // Assume we are making requests from the frontend
        $this->from('https://localhost/');

        // Authenticate the admin
        $response = $this->post('login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        // Assert that the admin is indeed authenticated using sanctum
        $this->assertAuthenticatedAs($admin, 'sanctum');

        // Create a user using a protected route with `auth:sanctum` middleware
        $response = $this->post('api/user', [
            'name' => 'John Doe',
            'email' => 'info@example.com',
            'password' => 'password',
        ]);

        // Assert that the route returns the created user
        $response->assertCreated()->assertJson([
            'name' => 'John Doe',
            'email' => 'info@example.com',
        ]);

        // Assert that the user was indeed created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'info@example.com',
        ]);

        // Assert that the created user has the correct credentials
        $this->assertCredentials([
            'email' => 'info@example.com',
            'password' => 'password',
        ]);
    }

    /**
     * @test
     */
    public function assert_credentials_using_api_tokens()
    {
        // Create the admin
        $admin = User::factory()->create();

        // Issue an API token for the admin
        $response = $this->post('api/sanctum/token', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'sanctum-268',
        ]);

        // Grab the token from the response
        $token = $response->content();

        // Create a user using a protected route with `auth:sanctum` middleware,
        // passing the API token as an Authorization header
        $response = $this->post('api/user', [
            'name' => 'John Doe',
            'email' => 'info@example.com',
            'password' => 'password',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        // Assert that the admin is indeed authenticated using sanctum with the API token
        $this->assertAuthenticatedAs($admin, 'sanctum');

        // Assert that the route returns the created user
        $response->assertCreated()->assertJson([
            'name' => 'John Doe',
            'email' => 'info@example.com',
        ]);

        // Assert that the user was indeed created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'info@example.com',
        ]);

        // Assert that the created user has the correct credentials
        $this->assertCredentials([
            'email' => 'info@example.com',
            'password' => 'password',
        ]);
    }
}
