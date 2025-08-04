<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Receipt;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class DashboardController extends Controller
{

    public function summery(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $dateTwentyDaysFromNow = Carbon::today()->addDays(20);
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        $user = User::find($request->get('user'));

        $products = Product::count();
        $stocks = Stock::count();
        if ($user->role_id !== 4) {
            $totalPriceToday = Receipt::whereDate('created_at', $today)->sum('amount');
        } else {
            $totalPriceToday = Receipt::whereDate('created_at', $today)
                ->where('user_id', $request->get('user'))
                ->sum('amount');
        }
        $soonToExpireCount = Stock::whereDate('expiration_date', '<', $dateTwentyDaysFromNow)
            ->count();
        $staffs = User::where('id', '>', 1)->count();

        if ($user->role_id !== 4) {
            $totalAmountThisWeek = Receipt::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('amount');
        } else {
            $totalAmountThisWeek = Receipt::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->where('user_id', $request->get('user'))
                ->sum('amount');
        }

        if ($user->role_id !== 4) {
            $lastWeekSales = Receipt::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->sum('amount');
        } else {
            $lastWeekSales = Receipt::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                ->where('user_id', $request->get('user'))
                ->sum('amount');
        }

        $result = Receipt::where('fully_paid', 0)
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
            'totalPriceToday' => $totalPriceToday,
            'soonToExpireCount' => $soonToExpireCount,
            'staffs' => $staffs,
            'totalAmountThisWeek' => $totalAmountThisWeek,
            'lastWeekSales' => $lastWeekSales,
            'outstanding' => $totalOutstanding,
            'totalStockValue' => $totalStockValue,
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
}
