<?php

/**
 * Copyright (C) ZubDev Digital Media - All Rights Reserved
 *
 * File: UnfillInventoryJob.php
 * Author: Zubayr Ganiyu
 *   Email: <seunexseun@gmail.com>
 *   Website: https://zubdev.net
 * Date: 8/28/25
 * Time: 2:32 PM
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

class UnfillInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

    protected int $unit;

    public function __construct(Product $product,
        int $unit,
    ) {
        $this->product = $product;
        $this->unit = $unit;
    }

    public function handle(): void
    {
        $stocks = Stock::where('product_id', $this->product->id)
            ->orderBy('expiration_date', 'asc')
            ->take($this->unit)
            ->delete();

        DashboardSummeryEvent::dispatch();
        RefreshInventoryEvent::dispatch();
    }
}
