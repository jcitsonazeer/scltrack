<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $table = 'schools';

    protected $fillable = [
        'school_name',
        'school_code',
        'school_address',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'status',
        'address',
        'created_by_id',
        'updated_by_id',
    ];

    protected $hidden = [
        'database_password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getHasDatabaseAttribute(): bool
    {
        return ! empty($this->database_name);
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'active' && $this->has_database;
    }
}
