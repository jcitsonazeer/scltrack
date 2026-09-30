<?php

namespace App\Services;

use App\Models\School;
use App\Models\Tenant\ParentModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ParentAuthService
{
    public function __construct(private TenantDatabaseService $tenantDatabaseService)
    {
    }

    public function findOpenSchool(School $school): void
    {
        if (! $school->has_database) {
            throw ValidationException::withMessages([
                'school_id' => 'Parent login is not available for this school yet.',
            ]);
        }

        if (! $this->tenantDatabaseService->databaseExists($school->database_name)) {
            throw ValidationException::withMessages([
                'school_id' => 'Parent login is not available for this school yet.',
            ]);
        }

        $this->tenantDatabaseService->switchTo($school->database_name);
    }

    public function login(School $school, string $username, string $password): ParentModel
    {
        $this->findOpenSchool($school);

        $parent = ParentModel::query()
            ->where('username', trim($username))
            ->where('is_active', true)
            ->first();

        if (! $parent || ! $parent->password || ! Hash::check($password, $parent->password)) {
            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        return $parent;
    }

    public function findById(int $parentId): ParentModel
    {
        $parent = ParentModel::query()
            ->whereKey($parentId)
            ->where('is_active', true)
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent' => 'Parent account was not found.',
            ]);
        }

        return $parent;
    }

    public function profile(School $school, ParentModel $parent): array
    {
        return [
            'id' => $parent->id,
            'full_name' => $parent->full_name,
            'username' => $parent->username,
            'phone' => $parent->phone,
            'email' => $parent->email,
            'address' => $parent->address,
            'emergency_contact_name' => $parent->emergency_contact_name,
            'emergency_contact_phone' => $parent->emergency_contact_phone,
            'is_active' => (bool) $parent->is_active,
            'school_id' => $school->id,
            'school_name' => $school->school_name,
        ];
    }
}
