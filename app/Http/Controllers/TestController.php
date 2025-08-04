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

    use App\Models\Stock;
    use Rap2hpoutre\FastExcel\FastExcel;

    class TestController extends Controller {
        public function index()
        {
//            $users = (new FastExcel)->import(public_path('stocks.csv'), function ($line) {
//                return Stock::create($line);
//            });
        }
	}
