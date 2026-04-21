<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalVariable extends Model
{
    public const DATA_TYPES = [
        'integer',
        'decimal',
        'text',
        'boolean',
        'date',
    ];

    protected $fillable = [
        'school_id',
        'category_id',
        'name',
        'slug',
        'unit',
        'data_type',
        'min_value',
        'max_value',
        'decimals',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:4',
            'max_value' => 'decimal:4',
            'decimals' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function category()
    {
        return $this->belongsTo(PhysicalVariableCategory::class, 'category_id');
    }

    public function recordValues()
    {
        return $this->hasMany(PhysicalVariableRecordValue::class);
    }
}