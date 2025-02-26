<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/products', [ProductsController::class, 'store']);
// เส้นทางสำหรับแก้ไขสินค้า (PUT หรือ PATCH ก็ได้)
Route::put('/products/{product}', [ProductsController::class, 'update']);

// เส้นทางสำหรับลบสินค้า
Route::delete('/products/{product}', [ProductsController::class, 'destroy']);
