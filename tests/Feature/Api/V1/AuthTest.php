<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'name', 'email', 'role'],
                     'authorization' => ['token', 'type'],
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);
    }

    /**
     * Test user login
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user',
                     'authorization' => ['token', 'type'],
                 ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Unauthorized']);
    }

    /**
     * Test authenticated user can access protected route
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = Auth::login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
    }

    /**
     * Test user can logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = auth::login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logout successful']);
    }

    /**
     * Test token refresh
     */
    public function test_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = Auth::login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'authorization' => ['token', 'type'],
                 ]);
    }
}
