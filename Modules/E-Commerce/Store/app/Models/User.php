<?php

namespace Modules\Ecommerce\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToClient;
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'gender',
        'dob',
        'provider',
        'provider_id',
        'is_admin',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
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
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Boot the model and register CRM sync events.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-create a CRM customer profile when a new user registers.
        static::created(function (self $user) {
            try {
                $parts = explode(' ', trim($user->name ?? ''), 2);
                $firstName = $parts[0] ?: explode('@', $user->email)[0];
                $lastName  = $parts[1] ?? '';

                \Modules\Ecommerce\CRM\Models\Customer::create([
                    'client_id'  => $user->client_id,
                    'user_id'    => $user->id,
                    'email'      => $user->email,
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'phone'      => $user->phone,
                    'source'     => $user->provider ?? 'direct',
                    'total_spent' => 0,
                    'order_count' => 0,
                    'average_order_value' => 0,
                    'engagement_score' => 0,
                    'churn_risk' => 'low',
                    'opt_in_email' => false,
                    'opt_in_sms' => false,
                    'forge_points' => 0,
                    'total_forge_points_earned' => 0,
                    'tier' => 'none',
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Failed to create CRM customer for user '.$user->id.': '.$e->getMessage());
            }
        });

        // Keep the CRM customer profile in sync when the user updates their details.
        static::updated(function (self $user) {
            if (! $user->isDirty(['name', 'email', 'phone', 'client_id'])) {
                return;
            }

            try {
                $parts = explode(' ', trim($user->name ?? ''), 2);
                $firstName = $parts[0] ?: explode('@', $user->email)[0];
                $lastName  = $parts[1] ?? '';

                \Modules\Ecommerce\CRM\Models\Customer::withoutGlobalScope('ecommerce-client')
                    ->where('user_id', $user->id)
                    ->update([
                        'client_id'  => $user->client_id,
                        'email'      => $user->email,
                        'first_name' => $firstName,
                        'last_name'  => $lastName,
                        'phone'      => $user->phone,
                        'source'     => $user->provider ?? 'direct',
                    ]);
            } catch (\Throwable $e) {
                \Log::warning('Failed to update CRM customer for user '.$user->id.': '.$e->getMessage());
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
