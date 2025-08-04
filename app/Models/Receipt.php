<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'reference',
        'amount',
        'user_id',
        'cash',
        'customer_name',
        'fully_paid',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
