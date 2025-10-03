<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's devices through the pivot table.
     */
    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the user's currently attached devices.
     */
    public function devices()
    {
        return $this->belongsToMany(Device::class, 'user_devices')
            ->withPivot('access_token', 'attached_at', 'detached_at')
            ->withTimestamps()
            ->wherePivotNull('detached_at');
    }

    /**
     * Get all alerts for this user.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
