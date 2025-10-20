<?php

namespace App\Http\Controllers;

use App\Events\DashboardSummeryEvent;
use App\Events\RefreshInventoryEvent;
use App\Jobs\RefillInventoryJob;
use App\Jobs\UnfillInventoryJob;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class InventoryController extends Controller
{
    /*
    ===============================================
    * Reading of data
    ===============================================
    */

    public function refill()
    {
        $products = Product::orderBy('name', 'asc')->get()
            ->map(function ($product) {
                return [
                    'label' => $product->name,
                    'value' => $product->id,
                ];
            });

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function index()
    {
        $products = Product::withCount('stocks')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function lowStock()
    {
        $products = Product::withCount('stocks')
            ->having('stocks_count', '<', 11)
            ->orderBy('stocks_count', 'asc')
            ->get();

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function sale()
    {

        $products = Product::orderBy('name', 'asc')
            ->whereHas('stocks')
            ->get()
            ->map(function ($product) {
                return [
                    'label' => $product->name,
                    'value' => $product->id,
                    'price' => (float) $product->price,
                ];
            });

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function viewReceipt(string $id)
    {
        $receipt = Receipt::find($id);

        return response()->json($receipt, ResponseAlias::HTTP_OK);
    }

    public function receiptReference(string $reference)
    {
        $receipt = Receipt::where('reference', $reference)
            ->with('user.role')
            ->first();

        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found',
            ], ResponseAlias::HTTP_NOT_FOUND);
        }

        return response()->json($receipt, ResponseAlias::HTTP_OK);
    }

    public function viewSaleReceipt(string $id)
    {
        $sales = Sale::with(['product' => function ($query) {
            $query->withCount('stocks');
        }, 'user.role'])->where('receipt_id', $id)->get();

        return response()->json($sales, ResponseAlias::HTTP_OK);
    }

    public function stocks(string $id)
    {
        $stocks = Stock::where('product_id', $id)->get();

        return response()->json($stocks, ResponseAlias::HTTP_OK);
    }

    public function outstandingReceipt()
    {
        $receipts = Receipt::where('fully_paid', 0)
            ->with('user.role')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($receipts, ResponseAlias::HTTP_OK);
    }

    public function loadInventories(Request $request): JsonResponse
    {
        $query = Product::withCount('stocks')
            ->orderBy('name', 'asc');

        if ($request->has('search')) {
            $keyword = $request->input('search');
            $query->where('name', 'LIKE', "%{$keyword}%");
        }

        $products = $query
            ->paginate(20);

        return \response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function loadOutstandingReceipt(Request $request): JsonResponse
    {
        $query = Receipt::where('fully_paid', '=', 0)
            ->orderBy('id', 'desc');

        if ($request->has('search')) {
            $keyword = $request->input('search');
            $query->where('customer_name', 'LIKE', "%{$keyword}%");
        }

        $receipts = $query->get();

        return \response()->json($receipts, ResponseAlias::HTTP_OK);
    }

    public function loadPrintData(string $reference)
    {
        $receipt = Receipt::where('reference', $reference)
            ->with(['user', 'sales.product'])
            ->first();

        return response()->json($receipt, ResponseAlias::HTTP_OK);
    }

    public function todaySalesReceipt(Request $request)
    {
        $today = Carbon::today();
        $query = Receipt::with('user.role')
            ->orderBy('id', 'desc')
            ->whereDate('created_at', $today);

        if ($request->user()->role_id !== 4) {
            $receipts = $query->get();
        } else {
            $receipts = $query->where('user_id', $request->user()->id)->get();
        }

        return response()->json($receipts, ResponseAlias::HTTP_OK);
    }

    public function weeklySalesReceipt(Request $request)
    {
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfWeek = Carbon::now()->startOfWeek();

        $query = Receipt::with('user.role')
            ->orderBy('id', 'desc')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);

        if ($request->user()->role_id !== 4) {
            $receipts = $query->get();
        } else {
            $receipts = $query->where('user_id', $request->user()->id)->get();
        }

        return response()->json($receipts, ResponseAlias::HTTP_OK);
    }

    public function expiringSoon()
    {
        $date = Carbon::today()->addMonths(3);
        $now = Carbon::now();

        $products = Product::query()
            ->withCount(['stocks' => function ($query) use ($now, $date) {
                $query->whereBetween('expiration_date', [$now, $date]);
            }])
            ->having('stocks_count', '>', 0)
            ->orderBy('stocks_count', 'desc')
            ->get();

        return response()->json($products, ResponseAlias::HTTP_OK);
    }

    public function expiringSoonStock(int $id)
    {
        $date = Carbon::today()->addMonths(3);
        $now = Carbon::now();

        $stocks = Stock::query()
            ->where('product_id', $id)
            ->whereBetween('expiration_date', [$now, $date])
            ->oldest()
            ->get();

        return response()->json($stocks, ResponseAlias::HTTP_OK);
    }

    public function searchSales(Request $request)
    {
        $startDate = $request->input('start');
        $endDate = $request->input('end');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = Receipt::whereBetween('created_at', [$start, $end]);

        if ($request->user()->role_id === 4) {
            $query->where('user_id', $request->user()->id);
        }

        $sumQuery = clone $query;
        $totalAmount = $sumQuery->sum('cash');

        $receipts = $query->with('user.role')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'receipts' => $receipts,
            'total_amount' => (float) $totalAmount,
        ], ResponseAlias::HTTP_OK);
    }

    public function recentSales(Request $request)
    {
        $query = Receipt::query();

        if ($request->user()->role_id === 4) {
            $query->where('user_id', $request->user()->id);
        }

        $receipts = $query->with('user.role')
            ->latest()
            ->take(50)
            ->get();

        return response()->json($receipts, ResponseAlias::HTTP_OK);
    }

    /*
    ===============================================
    * Creating of data
    ===============================================
    */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'expire' => 'required|date',
        ]);

        if ($validator->fails()) {
            return \response()->json([
                'message' => 'Error!', $validator->errors()->first(),
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        $product = Product::where('name', $request->input('name'))->first();
        if ($product) {
            if ($request->has('unit')) {
                for ($i = 0; $i < $request->input('unit'); $i++) {
                    Stock::create([
                        'product_id' => $product->id,
                        'expiration_date' => $request->get('expire'),
                    ]);
                }
                $product->price = $request->input('price', $product->price);
                $product->save();
            }
        } else {
            $product = Product::create([
                'name' => $request->input('name'),
                'stock' => $request->input('unit', 0),
                'price' => $request->input('price', '0'),
                'last_stock' => $request->input('unit', 0),
            ]);

            for ($i = 0; $i < $request->input('unit', 0); $i++) {
                Stock::create([
                    'product_id' => $product->id,
                    'expiration_date' => $request->get('expire'),
                ]);
            }
        }

        DashboardSummeryEvent::dispatch();

        return response()->json([
            'message' => 'New Product Has Been Added!',
        ], ResponseAlias::HTTP_CREATED);
    }

    /**
     * @throws \Throwable
     */
    public function saleStore(Request $request)
    {
        $items = $request->input('sales');
        $totalPrice = $request->input('total');
        $cash = $request->input('cash');
        $customerName = $request->input('customer');

        // Input validation
        if (count($items) === 0) {
            return response()->json([
                'message' => 'Please select at least one product!',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        // Use a transaction for data integrity
        DB::beginTransaction();

        try {
            // Step 1: Check stock availability
            foreach ($items as $item) {
                $stockCount = Stock::where('product_id', $item['product']['value'])->count();
                if ($stockCount < $item['quantity']) {
                    $product = Product::find($item['product']['value']);
                    DB::rollBack(); // Rollback before returning

                    return response()->json([
                        'message' => "Sorry! This product {$product->name} has only $stockCount unit left!",
                    ], ResponseAlias::HTTP_BAD_REQUEST);
                }
            }

            // Step 2: Create the receipt
            $lastReceipt = Receipt::latest()->first();
            $ref = ($lastReceipt ? $lastReceipt->id : 0) + 1;
            $paid = ($totalPrice - $cash < 1);

            $receipt = Receipt::create([
                'reference' => str_pad((string) $ref, 10, '0', STR_PAD_LEFT),
                'amount' => $totalPrice,
                'user_id' => $request->user()->id,
                'cash' => $cash,
                'customer_name' => $customerName,
                'fully_paid' => $paid,
            ]);

            $singleReceipt = Receipt::query()
                ->with('user.role')
                ->where('reference', $receipt->reference)
                ->first();

            // Step 3: Prepare and perform bulk operations
            $saleData = [];
            $stockIdsToDelete = [];

            foreach ($items as $item) {
                $stockItems = Stock::where('product_id', $item['product']['value'])
                    ->orderBy('expiration_date', 'asc')
                    ->take($item['quantity'])
                    ->pluck('id');

                $stockIdsToDelete = array_merge($stockIdsToDelete, $stockItems->toArray());

                $saleData[] = [
                    'user_id' => $request->user()->id,
                    'product_id' => $item['product']['value'],
                    'receipt_id' => $receipt->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Execute bulk delete and insert
            Stock::whereIn('id', $stockIdsToDelete)->delete();
            Sale::insert($saleData);

            DB::commit();

            DashboardSummeryEvent::dispatch();
            RefreshInventoryEvent::dispatch();

            return response()->json([
                'message' => 'Your Receipt Number is '.$receipt->reference,
                'receipt' => $receipt,
                'reference' => $receipt->reference,
                'single_receipt' => $singleReceipt,
                'summery' => 'Your total purchase is '.number_format($totalPrice, 0).' naira. Thank you for your patronize.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error for debugging
            \Log::error($e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'An error occurred during the transaction.',
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printReceiptSubmit(Request $request)
    {
        $receipt = Receipt::where('reference', $request->input('number'))->first();
        if (! $receipt) {
            return response()->json([
                'message' => 'Receipt not found!',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json($receipt, ResponseAlias::HTTP_OK);
    }

    /*
    ===============================================
    * Updating of data
    ===============================================
    */

    public function refillStore(Request $request, string $type)
    {
        $id = $request->input('id');
        $product = Product::withCount('stocks')->find($id);
        if ($type === 'add') {
            if (! $request->has('expire') || $request->input('expire') === null) {
                return response()->json([
                    'message' => 'Please set an expiry date',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            RefillInventoryJob::dispatch($product, $request->input('unit'), $request->input('expire'));

            return \response()->json([
                'message' => "$product->name Has Been Refilled Successfully!",
            ], ResponseAlias::HTTP_OK);
        }

        $totalStock = Stock::where('product_id', $product->id)->count();
        if ($request->input('unit') > $totalStock) {
            return \response()->json([
                'message' => 'The unit you provided is greater than the stock available.',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        UnfillInventoryJob::dispatch($product, $request->input('unit'));

        return \response()->json([
            'message' => "$product->name has been reduced",
        ], ResponseAlias::HTTP_OK);
    }

    public function deleteStock(Request $request, string $id)
    {
        Stock::destroy($id);

        DashboardSummeryEvent::dispatch();
        RefreshInventoryEvent::dispatch();

        return response()->json([
            'message' => 'Product Has Been Deleted!',
        ], ResponseAlias::HTTP_OK);
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        $product = Product::find($id);

        $product->name = $request->input('name');
        $product->price = $request->input('price');
        $product->save();

        return \response()->json([
            'message' => "$product->name Has Been Updated!",
        ], ResponseAlias::HTTP_OK);
    }

    public function updateReceiptCash(Request $request)
    {
        $receipt = Receipt::find($request->input('id'));
        $receipt->cash = $receipt->cash + $request->input('cash');
        if ($receipt->cash >= $receipt->amount) {
            $receipt->fully_paid = 1;
        } else {
            $receipt->fully_paid = 0;
        }
        $receipt->save();

        return response()->json([
            'message' => 'Receipt Has Been Updated!',
        ], ResponseAlias::HTTP_OK);
    }

    /*
    ===============================================
    * Deleting of data
    ===============================================
    */

    public function delete(Request $request, string $id)
    {
        Product::destroy($id);

        DashboardSummeryEvent::dispatch();
        RefreshInventoryEvent::dispatch();

        return \response()->json([
            'message' => 'Product Has Been Deleted!',
        ], ResponseAlias::HTTP_OK);
    }
}
