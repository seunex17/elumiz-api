<?php

/**
 * Copyright (C) ZubDev Digital Media - All Rights Reserved
 *
 * File: PushNotification.php
 * Author: Zubayr Ganiyu
 *   Email: <seunexseun@gmail.com>
 *   Website: https://zubdev.net
 * Date: 10/27/25
 * Time: 8:26 AM
 */

namespace App\Library;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;

class PushNotification
{
    public static function sendSingleNotification(User $user, array $data): void
    {
        $data['id'] = time();
        $device = Device::where('user_id', $user->id)->first();

        try {
            $message = CloudMessage::new()
                ->withData($data)
                ->withHighestPossiblePriority()
                ->toToken($device->device_token);
            app('firebase.messaging')->send($message);
        } catch (MessagingException $e) {
            Log::info($e->getMessage());
        }
    }
}
