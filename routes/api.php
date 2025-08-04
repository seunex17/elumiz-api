<?php

    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\InventoryController;
    use App\Http\Controllers\StaffController;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AUTH ROUTES
Route::post('/login', [AuthController::class, 'authenticate']);

Route::middleware('auth:sanctum')->group(function () {
    //Inventory Routes
    Route::prefix('inventory')->group(function () {
        Route::get('/refill', [InventoryController::class, 'refill']);
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/sale', [InventoryController::class, 'sale']);
        Route::get('/receipt/outstanding', [InventoryController::class, 'loadOutstandingReceipt']);
        Route::get('/receipt/{id}', [InventoryController::class, 'viewReceipt']);
        Route::get('/receipt/reference/{reference}', [InventoryController::class, 'receiptReference']);
        Route::get('/sale/receipt/{id}', [InventoryController::class, 'viewSaleReceipt']);
        Route::get('/stocks/{id}', [InventoryController::class, 'stocks']);
        Route::get('/outstanding', [InventoryController::class, 'outstandingReceipt']);
        Route::get('/load', [InventoryController::class, 'loadInventories']);

        Route::post('/store', [InventoryController::class, 'store']);
        Route::post('/refill/{type}', [InventoryController::class, 'refillStore']);
        Route::post('/sale', [InventoryController::class, 'saleStore']);
        Route::post('/print-receipt', [InventoryController::class, 'printReceiptSubmit']);
        Route::post('/stock/delete/{id}', [InventoryController::class, 'deleteStock']);
        Route::post('/update', [InventoryController::class, 'update']);
        Route::post('/delete/{id}', [InventoryController::class, 'delete']);
        Route::post('/update-receipt-cash', [InventoryController::class, 'updateReceiptCash']);
    });

    // Staff Routes
    Route::prefix('staff')->group(function () {
        Route::get('/manage', [StaffController::class, 'manage']);

        Route::post('/add', [StaffController::class, 'addNewStaff']);
        Route::post('/delete/{id}', [StaffController::class, 'deleteStaff']);
    });

    // Dashboard Route
    Route::prefix('dashboard')->group(function () {
        Route::get('/summery', [DashboardController::class, 'summery']);
        Route::get('/today-sale', [DashboardController::class, 'todaySale']);
        Route::get('/stocks', [DashboardController::class, 'stocks']);
    });
});

