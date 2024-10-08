<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NokInformation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['user_id', 'firstname', 'lastname', 'email', 'address', 'phone', 'relationship'];

    protected $hidden = [
        'user_id','created_at', 'updated_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
