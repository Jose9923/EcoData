<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherStation extends Model
{
    protected $fillable = [
        'school_id',
        'responsible_user_id',
        'name',
        'code',
        'location_name',
        'latitude',
        'longitude',
        'altitude',
        'installation_date',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'altitude' => 'decimal:2',
            'installation_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}