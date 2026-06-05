<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Audit\AuditLogger;
use App\Application\Auth\AccessTokenFactory;
use App\Application\Auth\RefreshTokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        AccessTokenFactory $accessTokenFactory,
        RefreshTokenService $refreshTokenService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $user = User::query()->where('email', $request->string('email')->lower())->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'Akun tidak aktif.');
        }

        $accessToken = $accessTokenFactory->create($user);
        [$refreshToken] = $refreshTokenService->create($user, $request);

        $user->update(['last_login_at' => now()]);
        $auditLogger->log($request, 'auth.login', $user);

        return response()->json([
            'message' => 'Login berhasil.',
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in_minutes' => config('auth_tokens.access_token_expiration_minutes'),
            'refresh_token' => $refreshToken,
            'user' => $user->load('company', 'employee'),
        ]);
    }

    public function refresh(
        RefreshTokenRequest $request,
        RefreshTokenService $refreshTokenService,
        AccessTokenFactory $accessTokenFactory
    ): JsonResponse {
        $result = $refreshTokenService->rotate($request->string('refresh_token'), $request);

        if (! $result) {
            abort(401, 'Refresh token tidak valid atau sudah kedaluwarsa.');
        }

        [$user, $newRefreshToken] = $result;

        return response()->json([
            'message' => 'Token diperbarui.',
            'access_token' => $accessTokenFactory->create($user),
            'token_type' => 'Bearer',
            'expires_in_minutes' => config('auth_tokens.access_token_expiration_minutes'),
            'refresh_token' => $newRefreshToken,
            'user' => $user->load('company', 'employee'),
        ]);
    }

    public function logout(Request $request, RefreshTokenService $refreshTokenService, AuditLogger $auditLogger): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        if ($request->filled('refresh_token')) {
            $refreshTokenService->revokePlainToken($request->string('refresh_token'));
        }

        $auditLogger->log($request, 'auth.logout');

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('company', 'employee'),
        ]);
    }
}
