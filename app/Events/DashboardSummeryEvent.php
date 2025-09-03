<?php
	/**
	 * Copyright (C) ZubDev Digital Media - All Rights Reserved
	 *
	 * File: DashboardSummeryEvent.php
	 * Author: Zubayr Ganiyu
	 *   Email: <seunexseun@gmail.com>
	 *   Website: https://zubdev.net
	 * Date: 9/2/25
	 * Time: 11:33 PM
	 */


	namespace App\Events;

    use Illuminate\Broadcasting\Channel;
    use Illuminate\Broadcasting\InteractsWithSockets;
    use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
    use Illuminate\Foundation\Events\Dispatchable;
    use Illuminate\Queue\SerializesModels;

	class DashboardSummeryEvent implements ShouldBroadcast {
        use Dispatchable, InteractsWithSockets, SerializesModels;

        /**
         * Create a new event instance.
         */
        public function __construct()
        {
            //
        }

        /**
         * Get the channels the event should broadcast on.
         */
        public function broadcastOn(): Channel
        {
            return new Channel('dashboard-summery');
        }
	}
