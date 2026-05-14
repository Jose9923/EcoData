<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalEvent extends Model
{
    protected $fillable = [
        'school_id',
        'created_by',
        'title',
        'description',
        'image_path',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}