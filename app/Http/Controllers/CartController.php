<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cart;
use App\Models\cart_items;
use App\Models\products;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function getCartItems()
    {
        $user = Auth::user();

        // ดึงข้อมูลตะกร้าของผู้ใช้
        $cart = Cart::where('users_user_id', $user->user_id)->first();

        if (!$cart) {
            return response()->json(['message' => 'ตะกร้าว่างเปล่า'], 200);
        }

        // ดึงสินค้าทั้งหมดในตะกร้า พร้อมข้อมูลสินค้าและร้านค้า
        $cartItems = $cart->cartItems()
            ->with([
                'product' => function ($query) {
                    $query->with('shop'); // ดึงข้อมูลร้านค้าของสินค้า
                }
            ])
            ->get();

        return response()->json(['cart_items' => $cartItems]);
    }

    public function getCartItemsByShop($shopId)
    {
        $user = Auth::user();

        // ดึงข้อมูลตะกร้าของผู้ใช้
        $cart = Cart::where('users_user_id', $user->user_id)->first();

        if (!$cart) {
            return response()->json(['message' => 'ตะกร้าว่างเปล่า'], 200);
        }

        // ดึงสินค้าทั้งหมดจากร้านที่ผู้ใช้เลือก
        $cartItems = $cart->cartItems()
            ->whereHas('product', function ($query) use ($shopId) {
                $query->where('shops_shop_id', $shopId);
            })
            ->with([
                'product' => function ($query) {
                    $query->with('shop');
                }
            ])
            ->get();

        return response()->json(['cart_items' => $cartItems]);
    }

    // เพิ่มสินค้าเข้าในตะกร้า
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();
        $cart = Cart::firstOrCreate(['users_user_id' => $user->user_id]);

        $productId = $request->product_id;

        // คำนวณสินค้าคงเหลือจาก stock_transaction
        $stockIn = \DB::table('stock_transaction')
            ->where('products_product_id', $productId)
            ->where('transaction_type', 'In')
            ->sum('quantity');

        $stockOut = \DB::table('stock_transaction')
            ->where('products_product_id', $productId)
            ->where('transaction_type', 'Out')
            ->sum('quantity');

        $currentStock = $stockIn - $stockOut;

        if ($request->quantity > $currentStock) {
            return response()->json([
                'error' => 'สินค้าคงเหลือไม่เพียงพอ',
                'remaining_stock' => $currentStock
            ], 400);
        }

        $cartItem = cart_items::updateOrCreate(
            ['cart_cart_id' => $cart->cart_id, 'products_product_id' => $productId],
            ['quantity' => $request->quantity, 'price' => products::find($productId)->price]
        );

        return response()->json(['message' => 'เพิ่มสินค้าในตะกร้าแล้ว', 'cart_item' => $cartItem]);
    }
    public function removeFromCart($cart_item_id)
    {
        $cartItem = cart_items::find($cart_item_id);

        if (!$cartItem) {
            return response()->json(['error' => 'ไม่พบสินค้านี้ในตะกร้า'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'ลบสินค้าออกจากตะกร้าสำเร็จ']);
    }
    public function updateCartItem(Request $request, $cart_item_id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $cartItem = cart_items::find($cart_item_id);
        if (!$cartItem) {
            return response()->json(['error' => 'ไม่พบสินค้านี้ในตะกร้า'], 404);
        }

        $product = products::find($cartItem->products_product_id);
        if ($request->quantity > $product->stock_quantity) {
            return response()->json(['error' => 'สินค้าคงเหลือไม่เพียงพอ'], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        return response()->json(['message' => 'อัปเดตจำนวนสินค้าสำเร็จ']);
    }

}

