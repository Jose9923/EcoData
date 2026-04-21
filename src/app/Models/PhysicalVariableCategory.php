<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalVariableCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function variables()
    {
        return $this->hasMany(PhysicalVariable::class, 'category_id');
    }
}