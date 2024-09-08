<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'reference',
        'amount',
        'charges',
        'prev_balance',
        'new_balance',
        'service_type',
        'transaction_type',
        'status',
        'channel',
        'is_commission',
        'narration',
        'image_url'
    ];

    protected $casts = [
        'is_commission' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
