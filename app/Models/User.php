<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'gender',
        'phone',
        'transaction_pin',
        'password',
        'image_url',
        'tier',
        'bvn',
        'device_id',
        'email_verified_at',
        'phone_verified_at',
        'date_of_birth',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'bvn'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'datetime',
        'password' => 'hashed',
        'tier' => 'integer',
    ];

    public function otp(): HasOne
    {
        return $this->hasOne(Otp::class, 'user_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function nok(): HasOne
    {
        return $this->hasOne(NokInformation::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    protected function getImageUrl($imageUrl): ?string
    {
        return $imageUrl != null ? $this->checkImageUrl($imageUrl) : null;
    }

    protected function checkImageUrl($imageUrl): string
    {
        return str_starts_with($imageUrl, 'http') ? $imageUrl : asset('/storage/' . $imageUrl);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn($value) => self::getImageUrl($value)
        );
    }
}
