<?php

/**
 * Copyright (C) ZubDev Digital Media - All Rights Reserved
 *
 * File: TestController.php
 * Author: Zubayr Ganiyu
 *   Email: <seunexseun@gmail.com>
 *   Website: https://zubdev.net
 * Date: 8/4/25
 * Time: 7:16 AM
 */

namespace App\Http\Controllers;

use App\Library\PushNotification;
use App\Models\User;

class TestController extends Controller
{
    public function index()
    {
        $user = User::find(1);
        PushNotification::sendSingleNotification($user, [
            'title' => 'hello world',
            'body' => 'hello world',
        ]);
    }
}
