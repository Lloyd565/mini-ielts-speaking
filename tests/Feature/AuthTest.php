<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Learner',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJson(['success' => true, 'message' => 'Registered successfully.'])
            ->assertJsonStructure(['success', 'data' => ['token'], 'message']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_it_rejects_registration_with_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane Learner',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_logs_in_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Logged in successfully.'])
            ->assertJsonStructure(['success', 'data' => ['token'], 'message']);
    }

    public function test_it_rejects_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'The given credentials are incorrect.']);
    }

    public function test_it_revokes_previously_issued_tokens_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);
        $oldToken = $user->createToken('api')->plainTextToken;

        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk();

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/speaking/attempts')
            ->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_it_logs_out_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Logged out successfully.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_throttles_repeated_login_attempts(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        foreach (range(1, 10) as $ignored) {
            $this->postJson('/api/auth/login', [
                'email' => 'jane@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_it_expires_issued_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/speaking/attempts')
            ->assertUnauthorized();
    }
}
