<?php

namespace App\Enums;

enum MeasureType: string
{
    case TEMPERATURE = 'temperature';
    case HUMIDITY = 'humidity';
    case BATTERY = 'battery';
    case PRESSURE = 'pressure';
}
