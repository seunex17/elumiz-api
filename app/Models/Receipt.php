<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = [
        'reference',
        'amount',
        'user_id',
        'cash',
        'customer_name',
        'fully_paid',
        'is_returned',
    ];

    protected $casts = [
        'fully_paid' => 'boolean',
        'amount' => 'string',
        'is_returned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'receipt_id');
    }
}
