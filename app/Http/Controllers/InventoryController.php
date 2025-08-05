<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $totalProduct = Product::count();
        $products = Product::withCount('stocks')
            ->orderBy('name', 'asc')
            ->take(10)
            ->paginate(20);

        return \response()->json([
            'products' => $products,
            'totalProduct' => $totalProduct,
        ], ResponseAlias::HTTP_OK);
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
            ->with('user')
            ->first();

        return response()->json($receipt, ResponseAlias::HTTP_OK);
    }

    public function viewSaleReceipt(string $id)
    {
        $sales = Sale::with('product')
            ->where('receipt_id', $id)->get();

        return response()->json($sales, ResponseAlias::HTTP_OK);
    }

    public function stocks(string $id)
    {
        $product = Product::find($id);
        $stocks = Stock::where('product_id', $id)->paginate(20);

        return response()->json([
            'stocks' => $stocks,
            'product' => $product,
        ], ResponseAlias::HTTP_OK);
    }

    public function outstandingReceipt()
    {
        $receipts = Receipt::where('fully_paid', 0)
            ->orderBy('id', 'desc')
            ->take(10)
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

        return response()->json([
            'receipt' => $receipt,
            'sales' => $receipt->sales,
        ], ResponseAlias::HTTP_OK);
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
                $stockCount = Stock::where('product_id', $item['id'])->count();
                if ($stockCount < $item['unit']) {
                    $product = Product::find($item['id']);
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

            // Step 3: Prepare and perform bulk operations
            $saleData = [];
            $stockIdsToDelete = [];

            foreach ($items as $item) {
                $stockItems = Stock::where('product_id', $item['id'])
                    ->orderBy('expiration_date', 'asc')
                    ->take($item['unit'])
                    ->pluck('id');

                $stockIdsToDelete = array_merge($stockIdsToDelete, $stockItems->toArray());

                $saleData[] = [
                    'user_id' => $request->user()->id,
                    'product_id' => $item['id'],
                    'receipt_id' => $receipt->id,
                    'quantity' => $item['unit'],
                    'price' => $item['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Execute bulk delete and insert
            Stock::whereIn('id', $stockIdsToDelete)->delete();
            Sale::insert($saleData);

            DB::commit();

            return response()->json([
                'message' => 'Your Receipt Number is ' . $receipt->reference,
                'receipt' => $receipt,
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
            if (! $request->has('expire')) {
                return response()->json([
                    'message' => 'Please set an expiry date',
                ], ResponseAlias::HTTP_BAD_REQUEST);
            }

            for ($i = 0; $i < $request->input('unit'); $i++) {
                Stock::create([
                    'product_id' => $product->id,
                    'expiration_date' => $request->input('expire'),
                ]);
            }

            $product->last_stock = $product->stocks_count + $request->input('unit');
            $product->save();

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

        $stocks = Stock::where('product_id', $product->id)
            ->orderBy('expiration_date', 'asc')
            ->take($request->input('unit'))
            ->delete();

        return \response()->json([
            'message' => "$product->name has been reduced",
        ], ResponseAlias::HTTP_OK);
    }

    public function deleteStock(Request $request, string $id)
    {
        Stock::destroy($id);

        return response()->json([
            'message' => "Product Has Been Deleted!",
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
        $receipt->cash = $request->input('cash');
        $receipt->fully_paid = $request->input('fully_paid');
        $receipt->save();

        return response()->json([
            'message' => "Receipt Has Been Updated!",
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

        return \response()->json([
            'message' => "Product Has Been Deleted!",
        ], ResponseAlias::HTTP_OK);
    }
}
