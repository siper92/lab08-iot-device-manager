<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_id',
        'access_token',
        'attached_at',
        'detached_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attached_at' => 'datetime',
        'detached_at' => 'datetime',
    ];

    /**
     * Boot the model and generate access token.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($userDevice) {
            if (empty($userDevice->access_token)) {
                $userDevice->access_token = Str::random(64);
            }
            if (empty($userDevice->attached_at)) {
                $userDevice->attached_at = now();
            }
        });
    }

    /**
     * Get the user that owns this device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device in this relationship.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Check if the device is currently attached.
     */
    public function isAttached(): bool
    {
        return is_null($this->detached_at);
    }

    /**
     * Detach the device from the user.
     */
    public function detach()
    {
        $this->detached_at = now();
        $this->save();
    }
}
