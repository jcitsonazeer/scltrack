<?php

namespace App\Services;

use App\Models\School;
use App\Models\Tenant\AdminAndDriver;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthService
{
    public function __construct(private TenantDatabaseService $tenantDatabaseService)
    {
    }

    public function findOpenSchool(School $school): void
    {
        if (! $school->has_database) {
            throw ValidationException::withMessages([
                'school_id' => 'Driver login is not available for this school yet.',
            ]);
        }

        if (! $this->tenantDatabaseService->databaseExists($school->database_name)) {
            throw ValidationException::withMessages([
                'school_id' => 'Driver login is not available for this school yet.',
            ]);
        }

        $this->tenantDatabaseService->switchTo($school->database_name);
    }

    public function login(School $school, string $username, string $password): AdminAndDriver
    {
        $this->findOpenSchool($school);

        $user = AdminAndDriver::query()
            ->where('username', trim($username))
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        return $user;
    }

    public function findById(int $userId): AdminAndDriver
    {
        $user = AdminAndDriver::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Driver / admin account was not found.',
            ]);
        }

        return $user;
    }

    public function profile(School $school, AdminAndDriver $user): array
    {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'user_role' => $user->user_role,
            'phone' => $user->phone,
            'email_id' => $user->email_id,
            'license_number' => $user->license_number,
            'license_expiry_date' => $user->license_expiry_date
                ? $user->license_expiry_date->format('d-m-Y')
                : null,
            'address' => $user->address,
            'is_active' => (bool) $user->is_active,
            'school_id' => $school->id,
            'school_name' => $school->school_name,
        ];
    }
}
