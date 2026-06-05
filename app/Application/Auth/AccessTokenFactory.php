<?php

namespace App\Application\Auth;

use App\Models\User;

class AccessTokenFactory
{
    public function create(User $user): string
    {
        $expiresAt = now()->addMinutes(config('auth_tokens.access_token_expiration_minutes'));

        return $user->createToken(
            name: 'payroll-access-token',
            abilities: [$user->role->value],
            expiresAt: $expiresAt
        )->plainTextToken;
    }
}
