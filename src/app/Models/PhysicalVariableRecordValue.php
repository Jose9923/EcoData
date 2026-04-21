<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalVariableRecordValue extends Model
{
    protected $fillable = [
        'physical_variable_record_id',
        'physical_variable_id',
        'value_numeric',
        'value_text',
        'value_boolean',
        'value_date',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'decimal:4',
            'value_boolean' => 'boolean',
            'value_date' => 'date',
        ];
    }

    public function record()
    {
        return $this->belongsTo(PhysicalVariableRecord::class, 'physical_variable_record_id');
    }

    public function variable()
    {
        return $this->belongsTo(PhysicalVariable::class, 'physical_variable_id');
    }

    public function getResolvedValueAttribute()
    {
        if (! is_null($this->value_numeric)) {
            return $this->value_numeric;
        }

        if (! is_null($this->value_text)) {
            return $this->value_text;
        }

        if (! is_null($this->value_boolean)) {
            return $this->value_boolean;
        }

        if (! is_null($this->value_date)) {
            return $this->value_date;
        }

        return null;
    }
}