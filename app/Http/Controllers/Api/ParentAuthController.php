<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LoginTokenService;
use App\Services\ParentAuthService;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentAuthController extends Controller
{
    public function __construct(
        private SchoolService $schoolService,
        private ParentAuthService $parentAuthService,
        private LoginTokenService $loginTokenService
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $school = $this->schoolService->findSchool((int) $validated['school_id']);
        $parent = $this->parentAuthService->login($school, $validated['username'], $validated['password']);

        return response()->json([
            'message' => 'Parent login successful.',
            'token' => $this->loginTokenService->make(LoginTokenService::PARENT, $parent->id, $school->id),
            'data' => $this->parentAuthService->profile($school, $parent),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $payload = $this->loginTokenService->read($request->bearerToken(), LoginTokenService::PARENT);

        $school = $this->schoolService->findSchool((int) $payload['school_id']);
        $parent = $this->parentAuthService->findById((int) $payload['user_id']);

        return response()->json([
            'message' => 'Parent profile loaded successfully.',
            'data' => $this->parentAuthService->profile($school, $parent),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json([
            'message' => 'Parent logged out successfully.',
        ]);
    }
}
