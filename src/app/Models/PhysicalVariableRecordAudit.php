<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalVariableRecordAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['physical_variable_record_id', 'user_id', 'action', 'before', 'after', 'created_at'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
