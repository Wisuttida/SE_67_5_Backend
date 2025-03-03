<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\StockController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\AddressesController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\UsersController;
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
Route::post('/login', [AuthController::class, 'login']); //แก้ตรง ->name('login');
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
Route::post('/logout', [AuthController::class, 'logout']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
Route::middleware('auth:sanctum')->group(function () {
    // จัดการข้อมูลผู้ใช้ (เฉพาะผู้ดูแลระบบสามารถเข้าถึงได้)
    Route::apiResource('users', UsersController::class);

    // จัดการที่อยู่สำหรับผู้ใช้ที่ล็อกอินอยู่
    Route::apiResource('addresses', AddressesController::class);

    // Endpoint สำหรับเรียกข้อมูลแผนที่
    // เส้นทางสำหรับเรียกข้อมูลแผนที่
    Route::get('map/provinces', [MapController::class, 'getProvinces']);
    Route::get('map/districts/{province_id}', [MapController::class, 'getDistricts']);
    Route::get('map/subdistricts/{district_id}', [MapController::class, 'getSubdistricts']);
});
Route::middleware('auth:api')->group(function () {
    // Checkout สร้างคำสั่งซื้อจากตะกร้า
    Route::post('/checkout', [OrdersController::class, 'checkout']);

    // สำหรับผู้ซื้อ: ตรวจสอบสถานะคำสั่งซื้อ
    Route::get('/orders', [OrdersController::class, 'listOrders']);
    Route::get('/orders/{id}', [OrdersController::class, 'show']);

    // อัปโหลดหลักฐานการชำระเงิน
    Route::post('/orders/{id}/upload-payment', [PaymentsController::class, 'uploadPaymentProof']);

    // สำหรับผู้ขาย: ดูคำสั่งซื้อที่เกี่ยวข้องกับร้านของตน
    Route::get('/seller/orders', [OrdersController::class, 'sellerOrders']);

    // สำหรับผู้ขาย: อัปเดตสถานะคำสั่งซื้อ (เช่น ยืนยันการชำระเงิน, จัดส่งสินค้า)
    Route::post('/orders/{id}/update-status', [OrdersController::class, 'updateStatus']);
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

