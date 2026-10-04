<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cluster_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google_linked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function canAccessAllClusters(): bool
    {
        return in_array($this->role, ['admin', 'keuangan'], true);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function toolUsages(): HasMany
    {
        return $this->hasMany(ToolUsage::class);
    }

    public function hasOperationalHistory(): bool
    {
        return $this->materialUsages()->exists()
            || $this->toolUsages()->exists()
            || StockIn::where('user_id', $this->id)->exists()
            || Tool::where('recorded_by_id', $this->id)->exists()
            || ToolReturnLog::where('reported_by', $this->id)->exists()
            || MaterialUsage::where('voided_by', $this->id)->exists()
            || ToolUsage::where('voided_by', $this->id)->exists()
            || InventoryAdjustment::where('user_id', $this->id)->exists()
            || MaterialToolRequest::where(function ($query) {
                $query->where('requester_id', $this->id)
                    ->orWhere('dispatcher_id', $this->id)
                    ->orWhere('approver_id', $this->id);
            })->exists();
    }
}
