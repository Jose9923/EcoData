<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalEventAcknowledgement extends Model
{
    protected $fillable = [
        'environmental_event_id',
        'user_id',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(EnvironmentalEvent::class, 'environmental_event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}