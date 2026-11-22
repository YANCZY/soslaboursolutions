<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Passport::tokensCan([
            'location-enrichment:write' => 'Submit location enrichment requests',
            'mobile:access' => 'Access the SOS mobile application',
            'companies:create' => 'Create company records',
            'sites:create' => 'Create site records',
            'users:create' => 'Provision client and contractor users, resolve sites, and queue invitations',
        ]);

        Passport::personalAccessTokensExpireIn(
            CarbonInterval::hours(8),
        );

        RateLimiter::for('mobile-login', function (Request $request): array {
            $email = $request->input('email');

            $email = is_string($email)
                ? mb_strtolower(trim($email))
                : '';

            return [
                Limit::perMinute(30)
                    ->by('ip:'.$request->ip()),

                Limit::perMinute(5)
                    ->by('account:'.hash('sha256', $email).'|'.$request->ip()),
            ];
        });

        RateLimiter::for('mobile-two-factor', function (Request $request): Limit {
            return Limit::perMinute(30)->by('ip:'.$request->ip());
        });

        // Passport::clientCredentialsTokensExpireIn(
        //     now()->addMinutes(1),
        // );

        Passport::clientCredentialsTokensExpireIn(
            CarbonInterval::days(31),
        );

    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
