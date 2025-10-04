<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SystemAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/login', [
            'email' => self::DEFAULT_USER_EMAIL,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_user_jwt_token_is_valid(): void
    {
        $response = $this->postJson('/login', [
            'email' => self::DEFAULT_USER_EMAIL,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $token = $response->json('token');
        $this->assertIsString($token);

        $service = app('App\Services\JwtService');
        $this->assertTrue($service->validateToken($token));

        $payload = $service->getPayload($token);
        $this->assertEquals('user', $payload->type, "Token type is not 'user'");
        $this->assertEquals(User::where('email', self::DEFAULT_USER_EMAIL)->first()->id, $payload->sub);

        $userDevices = User::where('email', self::DEFAULT_USER_EMAIL)->first()->devices;
        $this->assertEquals(count($payload->devices), count($userDevices), "Payload devices count does not match user's actual devices count");
        foreach ($payload->devices as $deviceId) {
            $this->assertTrue($userDevices->contains('id', $deviceId), "Payload device ID {$deviceId} not found in user's actual devices");
        }
    }

    public function test_user_login_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_user_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_user_login_requires_email(): void
    {
        $response = $this->postJson('/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_login_requires_password(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_login_requires_valid_email_format(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
