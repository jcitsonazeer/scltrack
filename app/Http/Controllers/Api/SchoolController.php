<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function __construct(private SchoolService $schoolService)
    {
    }

    public function index(): JsonResponse
    {
        $schools = $this->schoolService->getSelectableSchools();

        return response()->json([
            'message' => 'School list loaded successfully.',
            'data' => $this->schoolService->schoolList($schools),
        ]);
    }

    public function show(Request $request, int $schoolId): JsonResponse
    {
        $school = $this->schoolService->findSchool($schoolId);

        return response()->json([
            'message' => 'School details loaded successfully.',
            'data' => $this->schoolService->schoolDetails($school),
        ]);
    }
}
