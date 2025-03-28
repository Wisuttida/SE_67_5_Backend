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
use App\Http\Controllers\CustomOrderController;
use App\Http\Controllers\CustomerCustomOrderController;
use App\Http\Controllers\ShopCustomOrderController;
use App\Http\Controllers\BuyPostController;
use App\Http\Controllers\SalesPostController;
use App\Http\Controllers\SalesOfferController;
use App\Http\Controllers\BuyOfferController;
use App\Http\Controllers\FarmsController;
use App\Http\Controllers\ShopsController;
use App\Http\Controllers\IngredientOrdersController;
use App\Http\Controllers\IngredientController;



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
Route::get('/addresses', [AddressesController::class, 'index']);
Route::get('/addresses/{id}', [AddressesController::class, 'matchId']);
Route::post('/addresses', [AddressesController::class, 'store']);
Route::put('/addresses/{id}', [AddressesController::class, 'update']);
Route::patch('/addresses/{id}', [AddressesController::class, 'update']);
Route::delete('/addresses/{id}', [AddressesController::class, 'destroy']);
Route::middleware('auth:sanctum')->group(function () {
    // จัดการข้อมูลผู้ใช้ (เฉพาะผู้ดูแลระบบสามารถเข้าถึงได้)
    Route::get('/users', [UsersController::class, 'index']); // GET users
    Route::post('/users', [UsersController::class, 'store']); // POST user
    Route::post('/users/add', [UsersController::class, 'add']); // POST user
    Route::get('/user/get/{id}', [UsersController::class, 'show']); // GET user by ID
    Route::put('/users/{id}', [UsersController::class, 'update']); // PUT user by ID
    Route::put('/users/{id}/updateActivation', [UsersController::class, 'updateActivation']);
    Route::delete('/users/{id}', [UsersController::class, 'destroy']); // DELETE user by ID
    Route::apiResource('/addresses', AddressesController::class);
    Route::put('/user/updateProfile', [UsersController::class, 'updateProfile']);
    Route::put('/farm/update', [FarmsController::class, 'updateFarm']);
    Route::put('/shop/updateProfile', [ShopsController::class, 'updateProfile']);
    Route::put('/shop/updateBank', [ShopsController::class, 'updateBank']);
    Route::put('/shop/updateDescription', [ShopsController::class, 'updateDescription']);
    Route::get('/shop/get', [ShopsController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart']); // เพิ่มสินค้าในตะกร้า
    Route::delete('/cart/remove/{cart_item_id}', [CartController::class, 'removeFromCart']); // ลบสินค้าออกจากตะกร้า
    Route::put('/cart/update/{cart_item_id}', [CartController::class, 'updateCartItem']); // อัปเดตจำนวนสินค้าในตะกร้า
    Route::get('/cart/items', [CartController::class, 'getCartItems']); // ดูสินค้าในตะกร้า
    Route::get('/cart/items/shop/{shop_id}', [CartController::class, 'getCartItemsByShop']); // ดูสินค้าในตะกร้าจากร้านค้าที่เลือก
    // ------------------ Orders (คำสั่งซื้อ) ------------------
    Route::post('/orders/create', [OrdersController::class, 'createOrder']); // สร้างคำสั่งซื้อ
    Route::post('/orders/select-item', [OrdersController::class, 'createOrderFromSelectedItems']);  // สร้างคำสั่งซื้อจากสินค้าที่เลือก
    Route::put('/orders/update-status/{order_id}', [OrdersController::class, 'updateOrderStatus']); // อัปเดตสถานะคำสั่งซื้อ
    Route::get('/orders/show/{id}', [OrdersController::class, 'show']); // ดูรายละเอียดคำสั่งซื้อตาม order_id
    Route::get('/orders/list', [OrdersController::class, 'listOrders']); // ดูรายการคำสั่งซื้อทั้งหมดของผู้ใช้ที่ login
    Route::get('/orders/userPending', [OrdersController::class, 'ordersPending']); // ดูรายการคำสั่งซื้อทั้งหมดของผู้ใช้ที่ login
    Route::get('/orders/seller', [OrdersController::class, 'sellerOrders']); // ดูรายการคำสั่งซื้อทั้งหมดของผู้ใช้ที่ login ที่เป็น Seller
    Route::get('orders/status/{status}', [OrdersController::class, 'getOrdersByStatus']); //ดูรายการคำสั่งซื้อตามสถานะ
    // ------------------ Payments (การชำระเงิน) ------------------
    Route::get('/payments/shop', [PaymentsController::class, 'listPaymentsForShop']); // ดูรายการการชำระเงินสำหรับร้านค้า
    Route::post('/payments/upload/{order_id}', [PaymentsController::class, 'uploadPaymentProof']); // อัปโหลดหลักฐานการชำระเงิน
    Route::post('/payments/verify/{payment_id}', [PaymentsController::class, 'updatePaymentStatus']); // ยืนยันการชำระเงิน

    Route::get('/shops/accepted', [ShopCustomOrderController::class, 'getAcceptedShops']);

    // ------------------ สินค้าสั่งทำ ------------------
    // กลุ่มสำหรับ Custom Orders (สำหรับลูกค้า)
    Route::prefix('custom-orders')->group(function () {
        Route::post('/', [CustomOrderController::class, 'store']);
        Route::post('/{order_id}/tester/accept', [CustomerCustomOrderController::class, 'acceptTester']);
        Route::post('/{order_id}/tester/reject', [CustomerCustomOrderController::class, 'rejectTester']);
    });

    // กลุ่มสำหรับ Custom Orders (สำหรับร้านค้า)
    Route::prefix('shop/custom-orders')->group(function () {
        Route::post('/{order_id}/update-status', [ShopCustomOrderController::class, 'updateOrderStatus']);
        Route::post('/{order_id}/confirm-payment', [ShopCustomOrderController::class, 'confirmPayment']);
        Route::post('/{order_id}/ship', [ShopCustomOrderController::class, 'shipOrder']);
    });
    Route::get('orders/shipped', [OrdersController::class, 'getShippedOrdersForPosition4'])
        ->name('orders.getShippedForPosition4');
    Route::get('custom-orders/shipped', [CustomOrderController::class, 'getShippedCustomOrdersForPosition4'])
        ->name('customOrders.getShippedForPosition4');
    Route::put('orders/{order_id}/mark-delivered', [OrdersController::class, 'markOrderAsDelivered']);

});

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
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

Route::middleware('auth:sanctum')->group(function () {
    // Route สำหรับเรียกดูโพสต์รับซื้อทั้งหมด
    Route::get('/show-buyposts', [BuyPostController::class, 'showBuyPosts']);

    // Route สำหรับเรียกดูโพสต์ขายทั้งหมด
    Route::get('/show-salesposts', [SalesPostController::class, 'showSalesPosts']);

    Route::get('/buy-offers/{offerId}/details', [BuyOfferController::class, 'showOfferDetails']);
    Route::get('/offers-by-user-posts', [BuyOfferController::class, 'showOffersByUserPosts']);
    // Routes สำหรับ BuyPostController (ผู้ประกอบการโพสต์รับซื้อวัตถุดิบ)
    Route::get('/buy-posts', [BuyPostController::class, 'index']);
    Route::post('/buy-posts', [BuyPostController::class, 'store']);
    Route::put('/buy-posts/{id}', [BuyPostController::class, 'update']);
    Route::delete('/buy-posts/{id}', [BuyPostController::class, 'destroy']);

    // Routes สำหรับ BuyOfferController
    Route::post('/buy-offers/{buyPostId}', [BuyOfferController::class, 'storeOffer']);
    Route::post('/buy-offers/{offerId}/confirm', [BuyOfferController::class, 'confirmOffer']);
    Route::post('/buy-offers/{offerId}/reject', [BuyOfferController::class, 'rejectOffer']);

    // Routes สำหรับ SalesPostController (เกษตรกรโพสต์ขายวัตถุดิบ)
    Route::get('/sales-posts', [SalesPostController::class, 'index']);
    Route::post('/sales-posts', [SalesPostController::class, 'store']);
    Route::put('/sales-posts/{id}', [SalesPostController::class, 'update']);
    Route::delete('/sales-posts/{id}', [SalesPostController::class, 'destroy']);

    // Routes สำหรับ SalesOfferController
    Route::get('/shop/offers', [SalesOfferController::class, 'showShopOffers']);
    Route::get('/customer/offers', [SalesOfferController::class, 'showCustomerOffers']);
    Route::post('/sales-offers/{salesPostId}/store', [SalesOfferController::class, 'storeOffer']);
    Route::post('/sales-offers/{offerId}/confirm', [SalesOfferController::class, 'confirmOffer']);
    Route::post('/sales-offers/{offerId}/reject', [SalesOfferController::class, 'rejectOffer']);


    // Routes สำหรับ PaymentController
    Route::post('/orders/{order_id}/upload-payment-proof', [PaymentsController::class, 'uploadPaymentProof']);
    Route::post('/payments/{payment_id}/update-status', [PaymentsController::class, 'updatePaymentStatus']);
    Route::get('/shop/payments', [PaymentsController::class, 'listPaymentsForShop']);
    Route::get('/ingredients', [IngredientController::class, 'index']);


    //การตรวจสอบหลักฐานการชำระเงินของ farm
    Route::get('/payments/farm', [PaymentsController::class, 'listPaymentsForFarm']);
    //Route::post('/payments/ingredient-orders/{ingredientOrderId}/upload-proof', [PaymentsController::class, 'uploadPaymentProofForIngredientOrder']);
    Route::post('/payments/ingredient-orders/{paymentId}/update-status', [PaymentsController::class, 'updatePaymentStatusForIngredientOrder']);
    // Route สำหรับแสดงสถานะคำสั่งซื้อของร้านค้า
    Route::get('/ingredient-orders/shop', [IngredientOrdersController::class, 'showOrderStatusForShop']);
    // Route สำหรับแสดงสถานะคำสั่งซื้อของฟาร์ม
    Route::get('/ingredient-orders/farm', [IngredientOrdersController::class, 'showOrderStatusForFarm']);
    // Route สำหรับแสดงคำสั่งซื้อที่กรองตามสถานะของร้าน
    Route::get('/ingredient-orders/shops', [IngredientOrdersController::class, 'showOrdersByStatusForShop']);
    // Route สำหรับแสดงคำสั่งซื้อที่กรองตามสถานะของฟาร์ม
    Route::get('/ingredient-orders/farms', [IngredientOrdersController::class, 'showOrdersByStatusForFarm']);
    // Route สำหรับแสดงรายละเอียดคำสั่งซื้อ
    Route::get('/ingredient-orders/{orderId}', [IngredientOrdersController::class, 'showOrderDetails']);

    // Route สำหรับแก้ไขสถานะคำสั่งซื้อของฟาร์ม
    Route::put('/ingredient-orders/{orderId}/update-status', [IngredientOrdersController::class, 'updateOrderStatusForFarm']);
    Route::put('/ingredient-orders/{orderId}/update-to-shipped', [IngredientOrdersController::class, 'updateOrderStatusToShipped']);
    Route::get('/farm/pending-orders', [IngredientOrdersController::class, 'showPendingOrdersForFarm']);
});
