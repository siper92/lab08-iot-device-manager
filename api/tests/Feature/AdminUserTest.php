<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initUsers();

        // Get admin token for authenticated requests
        $response = $this->postJson('/admin/login', [
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => self::DEFAULT_ADMIN_PASSWORD,
        ]);

        $this->adminToken = $response->json('token');
    }

    public function test_admin_can_create_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
            ])
            ->assertJsonMissing(['password' => 'password123']);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
    }

    public function test_create_user_requires_name(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_user_requires_email(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'name' => 'John Doe',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_requires_password(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'name' => 'John Doe',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_create_user_requires_valid_email_format(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'name' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_user_requires_password_minimum_length(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => '12345',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        // Use existing user email
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', [
                'name' => 'Another User',
                'email' => self::DEFAULT_USER_EMAIL,
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_delete_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User deleted successfully']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_delete_user_fails_with_invalid_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/admin/users/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_user_detaches_associated_devices(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Device $device1 */
        $device1 = Device::factory()->create();
        /** @var Device $device2 */
        $device2 = Device::factory()->create();

        // Attach devices to user
        /** @var UserDevice $userDevice1 */
        $userDevice1 = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device1->id,
        ]);

        /** @var UserDevice $userDevice2 */
        $userDevice2 = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device2->id,
        ]);

        $this->assertNull($userDevice1->detached_at);
        $this->assertNull($userDevice2->detached_at);

        // Delete user
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/users/{$user->id}");

        $response->assertStatus(200);

        // Verify devices are detached
        $userDevice1->refresh();
        $userDevice2->refresh();

        $this->assertNotNull($userDevice1->detached_at);
        $this->assertNotNull($userDevice2->detached_at);
    }

    public function test_detached_devices_can_be_reattached_to_other_users(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Device $device */
        $device = Device::factory()->create();

        // Attach device to user1
        UserDevice::create([
            'user_id' => $user1->id,
            'device_id' => $device->id,
        ]);

        // Delete user1 (which detaches the device)
        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/users/{$user1->id}")
            ->assertStatus(200);

        // Verify device was detached
        /** @var UserDevice $oldUserDevice */
        $oldUserDevice = UserDevice::where('user_id', $user1->id)
            ->where('device_id', $device->id)
            ->first();
        $this->assertNotNull($oldUserDevice->detached_at);

        // Reattach device to user2
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/admin/devices/{$device->id}/attach", [
                'user_id' => $user2->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'user_id' => $user2->id,
                'device_id' => $device->id,
            ]);

        // Verify new attachment exists
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user2->id,
            'device_id' => $device->id,
            'detached_at' => null,
        ]);
    }

    public function test_deleting_user_with_already_detached_devices(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Device $device */
        $device = Device::factory()->create();

        // Attach and then detach device
        /** @var UserDevice $userDevice */
        $userDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_id' => $device->id,
        ]);
        $userDevice->detach();

        $this->assertNotNull($userDevice->detached_at);

        // Delete user
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/admin/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User deleted successfully']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_created_user_password_is_hashed(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'plainpassword',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/admin/users', $userData);

        $response->assertStatus(201);

        /** @var User $user */
        $user = User::where('email', 'jane.doe@example.com')->first();

        // Password should not be stored as plain text
        $this->assertNotEquals('plainpassword', $user->password);

        // Password should be hashed
        $this->assertTrue(password_verify('plainpassword', $user->password));
    }
}
