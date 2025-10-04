<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $device_id
 * @property string $measure_type
 * @property float|null $f_measure
 * @property string|null $s_measure
 * @property int|null $i_measure
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Alert> $alerts
 * @property-read int|null $alerts_count
 * @property-read \App\Models\Device $device
 * @property-read mixed $temperature
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereFMeasure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereIMeasure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereMeasureType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereRecordedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereSMeasure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeviceMeasurement whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DeviceMeasurement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'device_measurements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'measure_type',
        'f_measure',
        's_measure',
        'i_measure',
        'recorded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'f_measure' => 'float',
        'i_measure' => 'integer',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the device that owns this measurement.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Get alerts associated with this measurement.
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class, 'measurement_id');
    }

    /**
     * Get the temperature value if this is a temperature measurement.
     */
    public function getTemperatureAttribute()
    {
        return $this->measure_type === 'temperature' ? $this->f_measure : null;
    }
}
