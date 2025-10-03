<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_identifier' => 'DEV-' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true) . ' Sensor',
            'manufacturer' => $this->faker->company(),
            'description' => $this->faker->sentence(),
        ];
    }
}
