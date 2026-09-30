<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class SchoolService
{
    public function __construct(private TenantDatabaseService $tenantDatabaseService)
    {
    }

    public function getSelectableSchools(): Collection
    {
        return School::query()
            ->where('status', 'active')
            ->orderBy('school_name')
            ->get();
    }

    public function findSchool(int $schoolId): School
    {
        $school = School::query()->find($schoolId);

        if (! $school) {
            throw ValidationException::withMessages([
                'school_id' => 'Selected school was not found.',
            ]);
        }

        if ($school->status !== 'active') {
            throw ValidationException::withMessages([
                'school_id' => 'Selected school is not active.',
            ]);
        }

        return $school;
    }

    public function findOpenSchool(int $schoolId): School
    {
        $school = $this->findSchool($schoolId);

        if (! $school->has_database) {
            throw ValidationException::withMessages([
                'school_id' => 'Login is not available for this school yet.',
            ]);
        }

        return $school;
    }

    public function schoolList(Collection $schools): array
    {
        $list = [];

        foreach ($schools as $school) {
            $list[] = $this->schoolDetails($school);
        }

        return $list;
    }

    public function schoolDetails(School $school): array
    {
        $databaseExists = $school->has_database
            ? $this->tenantDatabaseService->databaseExists($school->database_name)
            : false;

        return [
            'id' => $school->id,
            'school_name' => $school->school_name,
            'school_code' => $school->school_code,
            'has_database' => $school->has_database,
            'database_exists' => $databaseExists,
            'can_login' => $school->status === 'active' && $databaseExists,
        ];
    }
}
