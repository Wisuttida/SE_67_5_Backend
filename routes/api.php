<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\StockController;
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

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/products', [ProductsController::class, 'store']);
// เส้นทางสำหรับแก้ไขสินค้า (PUT หรือ PATCH ก็ได้)
Route::put('/products/{product}', [ProductsController::class, 'update']);

// เส้นทางสำหรับลบสินค้า
Route::delete('/products/{product}', [ProductsController::class, 'destroy']);

// 1) แสดงรายการสินค้า (รูป, ชื่อสินค้า, ราคา) หรือค้นหา
Route::get('/products', [ProductsController::class, 'index']);
// 2) แสดงรายการสินค้า (รูป, ชื่อสินค้า, ราคา) หรือค้นหา สินค้าล่าสุด
Route::get('/latest-products', [ProductsController::class, 'latestProducts']);
// 3) แสดงรายละเอียดสินค้า (รูป, ชื่อสินค้า, ราคา, ชื่อร้านค้า, รายละเอียด)
Route::get('/products/{id}', [ProductsController::class, 'show']);

