<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
