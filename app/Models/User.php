<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\AccountAccessNotification;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Relations\HasOne;




#[Fillable(['first_name', 'last_name', 'user_type_id', 'client_id', 'email', 'password', 'status', 'phone', 'mobile'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::created(function (User $user) {
            $user->profile()->create([
                'travel_allowance' => 0,
                'travel_allowance_currency' => 'AUD',
            ]);
        });
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = ['name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Provide a compatibility full name for existing UI consumers.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(collect([
                $this->first_name,
                $this->last_name,
                $this->status,
                $this->phone,
                $this->mobile,
            ])->filter()->implode(' ')),
        );
    }

    /**
     * Get the user's type.
     */
    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    /**
     * Get the client that owns the user.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AccountAccessNotification($token));
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

}
