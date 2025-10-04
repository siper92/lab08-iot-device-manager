<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property string $alert_type
 * @property string $message
 * @property int|null $measurement_id
 * @property string $severity
 * @property \Illuminate\Support\Carbon $triggered_at
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Device $device
 * @property-read \App\Models\DeviceMeasurement|null $measurement
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereAlertType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereMeasurementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereTriggeredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Alert whereUserId($value)
 * @mixin \Eloquent
 */
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
