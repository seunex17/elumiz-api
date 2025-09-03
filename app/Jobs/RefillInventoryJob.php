<?php

/**
 * Copyright (C) ZubDev Digital Media - All Rights Reserved
 *
 * File: RefillInventoryJob.php
 * Author: Zubayr Ganiyu
 *   Email: <seunexseun@gmail.com>
 *   Website: https://zubdev.net
 * Date: 8/28/25
 * Time: 2:17 PM
 */

namespace App\Jobs;

use App\Events\DashboardSummeryEvent;
use App\Events\RefreshInventoryEvent;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefillInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

    protected int $unit;

    protected string $expire;

    public function __construct(Product $product,
        int $unit,
        string $expire,
    ) {
        $this->product = $product;
        $this->unit = $unit;
        $this->expire = $expire;
    }

    public function handle(): void
    {
        for ($i = 0; $i < $this->unit; $i++) {
            Stock::create([
                'product_id' => $this->product->id,
                'expiration_date' => $this->expire,
            ]);
        }

        $this->product->last_stock = $this->product->stocks_count + $this->unit;
        $this->product->save();

        DashboardSummeryEvent::dispatch();
        RefreshInventoryEvent::dispatch();
    }
}
