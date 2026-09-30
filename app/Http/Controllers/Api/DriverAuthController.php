<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DriverAuthService;
use App\Services\LoginTokenService;
use App\Services\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAuthController extends Controller
{
    public function __construct(
        private SchoolService $schoolService,
        private DriverAuthService $driverAuthService,
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
        $user = $this->driverAuthService->login($school, $validated['username'], $validated['password']);

        return response()->json([
            'message' => 'Driver / transport admin login successful.',
            'token' => $this->loginTokenService->make(LoginTokenService::DRIVER, $user->id, $school->id),
            'data' => $this->driverAuthService->profile($school, $user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $payload = $this->loginTokenService->read($request->bearerToken(), LoginTokenService::DRIVER);

        $school = $this->schoolService->findSchool((int) $payload['school_id']);
        $user = $this->driverAuthService->findById((int) $payload['user_id']);

        return response()->json([
            'message' => 'Driver / admin profile loaded successfully.',
            'data' => $this->driverAuthService->profile($school, $user),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json([
            'message' => 'Driver / transport admin logged out successfully.',
        ]);
    }
}
