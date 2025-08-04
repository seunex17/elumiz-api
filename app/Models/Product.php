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
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
