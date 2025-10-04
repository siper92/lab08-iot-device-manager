<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAdmin whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SystemAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
