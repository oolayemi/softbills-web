<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Wallet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'number', 'currency', 'balance'];

    protected $casts = [
        'balance' => 'decimal'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function virtualAccount(): HasOne
    {
        return $this->hasOne(VirtualAccount::class);
    }

    private static function generateWalletNumber(): string
    {
        $walletNumber = sprintf('%s%s', '10', rand(10000000, 99999999));
        if (self::where('number', $walletNumber)->first()) {
            self::generateWalletNumber();
        }

        return $walletNumber;
    }

    protected static function boot(): void
    {
        parent::boot();
        self::creating(function ($model) {
            $model->number = self::generateWalletNumber();
        });
    }
}
