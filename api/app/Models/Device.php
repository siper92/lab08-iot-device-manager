<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
