<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
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
        'alert_type',
        'message',
        'measurement_id',
        'severity',
        'triggered_at',
        'is_read',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'triggered_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    /**
     * Get the user that owns this alert.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device that triggered this alert.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get the measurement that triggered this alert.
     */
    public function measurement()
    {
        return $this->belongsTo(DeviceMeasurement::class, 'measurement_id');
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }

    /**
     * Scope a query to only include unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to filter by alert type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }
}
