<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        return DB::transaction(function () use ($data): JsonResponse {
            $user = User::query()
                ->where('email', mb_strtolower(trim($data['email'])))
                ->lockForUpdate()
                ->first();

            if (! $user || ! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'The email or password is incorrect.',
                ]);
            }

            $this->ensureEligible($user);

            if ($user->hasEnabledTwoFactorAuthentication()) {
                $challenge = bin2hex(random_bytes(32));

                Cache::put($this->challengeKey($challenge), [
                    'user_id' => $user->id,
                    'platform' => $data['platform'],
                    'fingerprint' => $this->fingerprint($user),
                ], now()->addMinutes(5));

                return $this->json([
                    'two_factor_required' => true,
                    'challenge_id' => $challenge,
                    'expires_in' => 300,
                ]);
            }

            return $this->issueToken($user, $data['platform']);
        });
    }

    public function twoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'code' => [
                'nullable',
                'required_without:recovery_code',
                'prohibits:recovery_code',
                'string',
                'regex:/^[0-9]{6}$/',
            ],
            'recovery_code' => [
                'nullable',
                'required_without:code',
                'prohibits:code',
                'string',
                'max:100',
            ],
        ]);

        $key = $this->challengeKey($data['challenge_id']);
        $challenge = Cache::get($key);

        abort_unless(
            is_array($challenge),
            410,
            'Your login attempt expired. Sign in again.',
        );

        return DB::transaction(function () use ($data, $key, $challenge): JsonResponse {
            $user = User::query()
                ->lockForUpdate()
                ->find($challenge['user_id']);

            // Re-read after obtaining the lock to prevent challenge reuse
            // by concurrent mobile verification requests.
            $current = Cache::get($key);

            if (
                ! $user ||
                ! is_array($current) ||
                ! hash_equals($current['fingerprint'], $this->fingerprint($user)) ||
                ! $user->hasEnabledTwoFactorAuthentication()
            ) {
                Cache::forget($key);

                abort(410, 'Your login attempt expired. Sign in again.');
            }

            $this->ensureEligible($user);

            // Limit attempts per account, across all its challenges.
            $limitKey = 'mobile-two-factor-user:'.$user->id;

            abort_if(
                RateLimiter::tooManyAttempts($limitKey, 5),
                429,
                'Too many verification attempts. Wait five minutes and sign in again.',
            );

            RateLimiter::hit($limitKey, 300);

            if (! empty($data['recovery_code'])) {
                $validCode = collect($user->recoveryCodes())
                    ->first(fn (string $code): bool =>
                        hash_equals($code, $data['recovery_code'])
                    );

                if (! $validCode) {
                    throw ValidationException::withMessages([
                        'recovery_code' => 'The recovery code is invalid.',
                    ]);
                }

                $user->replaceRecoveryCode($validCode);
            } else {
                $valid = app(TwoFactorAuthenticationProvider::class)->verify(
                    Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                    $data['code'],
                );

                if (! $valid) {
                    throw ValidationException::withMessages([
                        'code' => 'The authentication code is invalid or has already been used.',
                    ]);
                }
            }

            $response = $this->issueToken($user, $current['platform']);

            Cache::forget($key);

            return $response;
        });
    }

    public function me(Request $request): JsonResponse
    {
        return $this->json([
            'user' => $this->userData($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->revoke();

        return $this->json([
            'message' => 'Signed out.',
        ]);
    }

    private function ensureEligible(User $user): void
    {
        abort_unless(
            $user->status === 'active',
            403,
            'Your account is inactive. Contact your administrator.',
        );

        abort_unless(
            $user->hasVerifiedEmail(),
            403,
            'Verify your email through the web portal before signing in.',
        );
    }

    private function issueToken(User $user, string $platform): JsonResponse
    {
        $result = $user->createToken(
            'SOS Mobile:'.$platform,
            ['mobile:access'],
        );

        return $this->json([
            'two_factor_required' => false,
            'token_type' => 'Bearer',
            'access_token' => $result->accessToken,
            'expires_in' => $result->expiresIn,
            'user' => $this->userData($user),
        ]);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];
    }

    private function challengeKey(string $challenge): string
    {
        return 'mobile-login:'.hash('sha256', $challenge);
    }

    private function fingerprint(User $user): string
    {
        return hash('sha256', json_encode([
            $user->password,
            $user->two_factor_secret,
            $user->getRawOriginal('two_factor_confirmed_at'),
        ], JSON_THROW_ON_ERROR));
    }

    private function json(array $data): JsonResponse
    {
        return response()
            ->json($data)
            ->header('Cache-Control', 'no-store')
            ->header('Pragma', 'no-cache');
    }
}
