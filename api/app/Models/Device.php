<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $device_identifier
 * @property string|null $manufacturer
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Alert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \App\Models\UserDevice|null $currentUserDevice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DeviceMeasurement> $measurements
 * @property-read int|null $measurements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserDevice> $userDevices
 * @property-read int|null $user_devices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\DeviceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereDeviceIdentifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Device withoutTrashed()
 * @mixin \Eloquent
 */
class Device extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_identifier',
        'manufacturer',
        'name',
        'description',
    ];

    /**
     * Get the device's ownership records.
     */
    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the users who currently own this device.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_devices')
            ->withPivot('access_token', 'attached_at', 'detached_at')
            ->withTimestamps()
            ->wherePivotNull('detached_at');
    }

    /**
     * Get all measurements for this device.
     */
    public function measurements()
    {
        return $this->hasMany(DeviceMeasurement::class);
    }

    /**
     * Get all alerts for this device.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the currently active user-device relationship.
     */
    public function currentUserDevice()
    {
        return $this->hasOne(UserDevice::class)->whereNull('detached_at');
    }
}
