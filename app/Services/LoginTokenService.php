<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class LoginTokenService
{
    public const PARENT = 'parent';

    public const DRIVER = 'driver';

    public function make(string $userType, int $userId, int $schoolId): string
    {
        $payload = [
            'user_type' => $userType,
            'user_id' => $userId,
            'school_id' => $schoolId,
            'expires_at' => now()->addDays(30)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    public function read(?string $token, string $userType): array
    {
        if (empty($token)) {
            throw ValidationException::withMessages([
                'token' => 'Login token is missing.',
            ]);
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'token' => 'Login token is invalid.',
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'token' => 'Login token is invalid.',
            ]);
        }

        if (($payload['user_type'] ?? '') !== $userType) {
            throw ValidationException::withMessages([
                'token' => 'Login token is not valid for this account type.',
            ]);
        }

        if (! isset($payload['expires_at']) || $payload['expires_at'] < now()->timestamp) {
            throw ValidationException::withMessages([
                'token' => 'Login token has expired. Please login again.',
            ]);
        }

        return $payload;
    }
}
