<?php

namespace App\Application\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function create(User $user, Request $request): array
    {
        $plain = Str::random(80);

        $token = RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'expires_at' => now()->addDays(config('auth_tokens.refresh_token_expiration_days')),
        ]);

        return [$plain, $token];
    }

    public function findValid(string $plain): ?RefreshToken
    {
        $token = RefreshToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->first();

        if (! $token || ! $token->isValid()) {
            return null;
        }

        return $token;
    }

    public function rotate(string $plain, Request $request): ?array
    {
        $current = $this->findValid($plain);

        if (! $current) {
            return null;
        }

        $current->update(['revoked_at' => now()]);

        [$newPlain, $newToken] = $this->create($current->user, $request);

        return [$current->user, $newPlain, $newToken];
    }

    public function revokePlainToken(string $plain): void
    {
        $token = RefreshToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->first();

        $token?->update(['revoked_at' => now()]);
    }
}
