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
use App\Http\Controllers\CartController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\API\TambonController;

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
Route::post('/registerShop', [AuthController::class, 'registerShop']);
Route::post('/registerFarm', [AuthController::class, 'registerFarm']);
Route::post('/login', [AuthController::class, 'login']); //แก้ตรง ->name('login');
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

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

});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart']); // เพิ่มสินค้าในตะกร้า
    Route::delete('/cart/remove/{cart_item_id}', [CartController::class, 'removeFromCart']); // ลบสินค้าออกจากตะกร้า
    Route::put('/cart/update/{cart_item_id}', [CartController::class, 'updateCartItem']); // อัปเดตจำนวนสินค้าในตะกร้า
    Route::get('/cart/items', [CartController::class, 'getCartItems']); // ดูสินค้าในตะกร้า
    Route::get('/cart/items/shop/{shop_id}', [CartController::class, 'getCartItemsByShop']); // ดูสินค้าในตะกร้าจากร้านค้าที่เลือก
    // ------------------ Orders (คำสั่งซื้อ) ------------------
    Route::post('/orders/create', [OrdersController::class, 'createOrder']); // สร้างคำสั่งซื้อ
    Route::put('/orders/update-status/{order_id}', [OrdersController::class, 'updateOrderStatus']); // อัปเดตสถานะคำสั่งซื้อ
    Route::get('/orders/track/{order_id}', [OrdersController::class, 'trackOrder']); // ติดตามสถานะคำสั่งซื้อ
    Route::get('/orders/show/{id}', [OrdersController::class, 'show']);
    Route::get('/orders/list', [OrdersController::class, 'listOrders']);
    Route::get('/orders/seller', [OrdersController::class, 'sellerOrders']);
    Route::get('/orders/status/{status}', [OrdersController::class, 'getOrdersByStatus']);
    // ------------------ Payments (การชำระเงิน) ------------------
    Route::post('/payments/upload/{order_id}', [PaymentsController::class, 'uploadPaymentProof']); // อัปโหลดหลักฐานการชำระเงิน
    Route::put('/payments/verify/{payment_id}', [PaymentsController::class, 'verifyPayment']); // ยืนยันการชำระเงิน
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

Route::get('/provinces', [TambonController::class, 'getProvinces']);
Route::get('/amphoes', [TambonController::class, 'getAmphoes']);
Route::get('/tambons', [TambonController::class, 'getTambons']);
Route::get('/zipcodes', [TambonController::class, 'getZipcodes']);
