<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentModel extends TenantModel
{
    protected $table = 'parents';

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'username',
        'password',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
