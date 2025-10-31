<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Receipt;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class DashboardController extends Controller
{
    public function summery(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $dateThreeMonthFromNow = Carbon::today()->addMonths(3);
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        $user = User::find($request->user()->id);

        $products = Product::count();
        $stocks = Stock::count();
        if ($user->role_id !== 4) {
            $totalPriceToday = Receipt::whereDate('created_at', $today)
                ->where('is_returned', 0)
                ->sum('cash');
        } else {
            $totalPriceToday = Receipt::whereDate('created_at', $today)
                ->where('user_id', $user->id)
                ->where('is_returned', 0)
                ->sum('cash');
        }

        if ($user->role_id !== 4) {
            $totalPriceMadeToday = Receipt::whereDate('created_at', $today)
                ->where('is_returned', 0)
                ->sum('amount');
        } else {
            $totalPriceMadeToday = Receipt::whereDate('created_at', $today)
                ->where('is_returned', 0)
                ->where('user_id', $user->id)
                ->sum('amount');
        }

        $soonToExpireCount = Stock::query()->whereBetween('expiration_date', [$now, $dateThreeMonthFromNow])
            ->count();
        $staffs = User::where('id', '>', 1)->count();

        if ($user->role_id !== 4) {
            $totalAmountThisWeek = Receipt::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->where('is_returned', 0)
                ->sum('cash');
        } else {
            $totalAmountThisWeek = Receipt::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->where('is_returned', 0)
                ->where('user_id', $user->id)
                ->sum('cash');
        }

        if ($user->role_id !== 4) {
            $lastWeekSales = Receipt::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->where('is_returned', 0)
                ->sum('cash');
        } else {
            $lastWeekSales = Receipt::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->where('is_returned', 0)
                ->where('user_id', $request->get('user'))
                ->sum('cash');
        }

        $result = Receipt::where('fully_paid', 0)
            ->where('is_returned', 0)
            ->select(
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(cash) as total_cash'),
                DB::raw('SUM(amount - cash) as total_outstanding')
            )
            ->first();

        $totalOutstanding = $result->total_outstanding ?? 0;

        $totalStockValue = Product::query()
            ->selectRaw('SUM(products.price * stock_counts.count) as total_value')
            ->joinSub(function ($query) {
                $query->from('stocks')
                    ->select('product_id', DB::raw('count(*) as count'))
                    ->groupBy('product_id');
            }, 'stock_counts', 'stock_counts.product_id', '=', 'products.id')
            ->value('total_value');

        return response()->json([
            'products' => $products,
            'stocks' => $stocks,
            'totalPriceToday' => (float) $totalPriceToday,
            'soonToExpireCount' => $soonToExpireCount,
            'staffs' => $staffs,
            'totalAmountThisWeek' => (float) $totalAmountThisWeek,
            'lastWeekSales' => (float) $lastWeekSales,
            'outstanding' => (float) $totalOutstanding,
            'totalStockValue' => (float) $totalStockValue,
            'totalPriceMadeToday' => (float) $totalPriceMadeToday,
        ], ResponseAlias::HTTP_OK);
    }

    public function todaySale(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $user = User::find($request->get('user'));

        if ($user->role_id !== 4) {
            $sales = Receipt::with('user')
                ->whereDate('created_at', $today)
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get();
        } else {
            $sales = Receipt::with('user')
                ->whereIn('user_id', $request->get('user'))
                ->whereDate('created_at', $today)
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get();
        }

        return response()->json($sales, ResponseAlias::HTTP_OK);
    }

    public function stocks(Request $request): JsonResponse
    {
        $products = Product::withCount('stocks')
            ->orderBy('stocks_count', 'asc')
            ->take(100)
            ->get();

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function dailySale(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $startDate = $today->startOfWeek(CarbonInterface::MONDAY)->toDateString();
        $endDate = $today->endOfWeek(CarbonInterface::SUNDAY)->addDay()->toDateString();

        // 2. Build the query
        $query = DB::table('receipts')
            ->select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('SUM(amount) as daily_total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('sale_date')
            ->orderBy('sale_date', 'asc');

        if ($request->user()->role_id !== 4) {
            $sales = $query->get();
        } else {
            $sales = $query->where('user_id', $request->user()->id)->get();
        }

        $dailySales = collect(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])
            ->mapWithKeys(function ($dayName) {
                return [$dayName => 0];
            });

        foreach ($sales as $sale) {
            $date = Carbon::parse($sale->sale_date);
            $dayName = $date->format('D');
            $dailySales[$dayName] = $sale->daily_total;
        }

        $dataPoints = $dailySales->values()->all();
        $labels = $dailySales->keys()->all();

        return response()->json([
            'labels' => $labels,
            'data' => $dataPoints,
        ]);
    }
}
