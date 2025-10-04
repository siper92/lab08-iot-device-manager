<?php

namespace Feature;

use App\Models\SystemAdmin;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => self::DEFAULT_ADMIN_PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_admin_jwt_token_structure_is_valid(): void
    {
        $response = $this->postJson('/admin/login', [
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => self::DEFAULT_ADMIN_PASSWORD,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $service = new JwtService();

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->assertTrue($service->validateToken($token));

        $payload = $service->getPayload($token);
        $this->assertEquals('admin', $payload->type);
        $this->assertEquals(SystemAdmin::where('email', self::DEFAULT_ADMIN_EMAIL)->first()->id, $payload->sub);
    }
}
