<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'last_stock',
        'cost_price',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
