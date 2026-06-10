<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $with = ['roleRef'];

    protected $fillable = [
        'name',
        'email',
        'yandex_id',
        'yandex_email',
        'password',
        'role_id',
        'delivery_address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'role',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->roleRef?->code,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return ['role_id' => null];
                }

                return [
                    'role_id' => UserRole::idFor($value),
                ];
            },
        );
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'administrator'], true);
    }
}
