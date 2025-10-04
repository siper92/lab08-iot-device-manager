<?php

namespace Tests;

use App\Models\Device;
use App\Models\SystemAdmin;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Pagination;

abstract class TestCase extends BaseTestCase
{
    const DEFAULT_USER_EMAIL = 'test1@lab08.test';
    const DEFAULT_USER_PASSWORD = 'password123';
    const DEFAULT_ADMIN_EMAIL = 'test_admin@lab08.test';
    const DEFAULT_ADMIN_PASSWORD = 'adminpass123';

    public function initConfig(): void
    {
        config(['app_custom.page_limit' => Pagination::DEFAULT_PER_PAGE]);
    }

    public function initUsers(): void
    {
        $this->initUserWithDevices(
            self::DEFAULT_USER_EMAIL,
            2,
        );

        $admin = SystemAdmin::create([
            'email' => self::DEFAULT_ADMIN_EMAIL,
            'password' => Hash::make(self::DEFAULT_ADMIN_PASSWORD),
        ]);
    }

    public function initUserWithDevices(
        string $email = self::DEFAULT_USER_EMAIL,
        int $deviceCount = 3
    ): void
    {
        $user = User::create([
            'name' => 'User ' . uniqid(),
            'email' =>$email,
            'password' => Hash::make(self::DEFAULT_USER_PASSWORD),
        ]);

        for ($i = 0; $i < $deviceCount; $i++) {
            $device = Device::factory()->create();
            if (!$device) {
                continue;
            }
            UserDevice::create([
                'user_id' => $user->id,
                'device_id' => $device->id,
                'access_token' => "user_{$user->id}_device_{$device->id}",
                'attached_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
