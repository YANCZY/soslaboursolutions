<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;


#[Fillable([
    'company_name',
    'company_type',
    'trade',
    'industry',
    'industry_description',
    'phone',
    'website',
    'company_address',
    'company_address_2',
    'company_address_city',
    'company_address_state',
    'company_address_country',

])]
class Client extends Model
{
    use HasFactory;

    public const RELATED_RECORDS = [
        'users',
        'assignedUsers',
        'workDetails',
        'attendances',
        'travelAllowances',
        'saveRequests',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('clients.is_active', true);
    }

    public function workDetails(): HasMany
    {
        return $this->hasMany(UserCompanyWorkDetail::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function travelAllowances(): HasMany
    {
        return $this->hasMany(TravelAllowance::class);
    }

    public function saveRequests(): HasMany
    {
        return $this->hasMany(ClientSaveRequest::class);
    }

    public function hasRelatedRecords(): bool
    {
        foreach (self::RELATED_RECORDS as $relation) {
            if ($this->{$relation}()->exists()) {
                return true;
            }
        }

        return false;
    }

    public static function assertActiveForUse(
        ?int $companyId,
        string $field = 'client_id',
    ): void {
        if (! $companyId || ! static::query()
            ->active()
            ->whereKey($companyId)
            ->exists()) {
            throw ValidationException::withMessages([
                $field => 'This company is inactive or unavailable.',
            ]);
        }
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
