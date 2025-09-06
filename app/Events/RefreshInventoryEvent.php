<?php

/**
 * Copyright (C) ZubDev Digital Media - All Rights Reserved
 *
 * File: RefreshInventoryEvent.php
 * Author: Zubayr Ganiyu
 *   Email: <seunexseun@gmail.com>
 *   Website: https://zubdev.net
 * Date: 9/2/25
 * Time: 11:47 PM
 */

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefreshInventoryEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct() {}

    public function broadcastOn(): Channel
    {
        return new Channel('refresh-inventory');
    }
}
