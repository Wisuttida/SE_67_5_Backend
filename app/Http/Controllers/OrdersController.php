<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cart;
use App\Models\orders;
use App\Models\order_items;
use App\Models\products;
use App\Models\stock_transaction;
use App\Models\users;
use App\Models\shops;
use App\Models\addresses;
use App\Models\cart_items;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function createOrder()
    {
        $user = auth()->user();
        $cart = Cart::where('users_user_id', $user->user_id)->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['error' => 'ตะกร้าสินค้าของคุณว่างเปล่า'], 400);
        }

        $defaultAddress = $user->addresses()->where('is_default', 1)->first();
        if (!$defaultAddress) {
            return response()->json(['error' => 'โปรดตั้งค่าที่อยู่หลักก่อนทำการสั่งซื้อ'], 400);
        }

        DB::beginTransaction();

        try {
            $cartItems = $cart->cartItems()->with('product')->get();
            $groupedItems = $cartItems->groupBy('product.shops_shop_id'); // แยกสินค้าตามร้านค้า

            $orders = [];
            foreach ($groupedItems as $shop_id => $items) {
                $order = orders::create([
                    'users_user_id' => $user->user_id,
                    'total_amount' => 0,
                    'status' => 'pending',
                    'addresses_address_id' => $defaultAddress->address_id,
                    'shops_shop_id' => $shop_id, // เพิ่ม shop_id ให้รู้ว่าคำสั่งซื้อนี้มาจากร้านไหน
                ]);

                $totalAmount = 0;
                foreach ($items as $item) {
                    order_items::create([
                        'orders_order_id' => $order->order_id,
                        'products_product_id' => $item->products_product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price
                    ]);
                    $totalAmount += $item->quantity * $item->price;
                }

                $order->update(['total_amount' => $totalAmount]);
                $orders[] = $order;
            }

            // ล้างตะกร้าหลังจากสั่งซื้อสำเร็จ
            $cart->cartItems()->delete();

            DB::commit();
            return response()->json(['message' => 'คำสั่งซื้อถูกสร้างแล้ว', 'orders' => $orders]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ'], 500);
        }
    }

    public function updateOrderStatus(Request $request, $order_id)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled']);

        $order = orders::with('orderItems.product')->find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        $currentStatus = $order->status;
        $newStatus = $request->status;

        if ($newStatus === 'confirmed' && $currentStatus === 'pending') {
            foreach ($order->orderItems as $item) {
                if ($item->product->stock_quantity < $item->quantity) {
                    return response()->json(['error' => 'สต็อกสินค้าไม่เพียงพอสำหรับ ' . $item->product->name], 400);
                }

                \DB::table('stock_transaction')->insert([
                    'transaction_type' => 'Out',
                    'quantity' => $item->quantity,
                    'transaction_date' => now(),
                    'products_product_id' => $item->products_product_id
                ]);

                \DB::table('products')
                    ->where('product_id', $item->products_product_id)
                    ->decrement('stock_quantity', $item->quantity);
            }
        }

        $order->update(['status' => $newStatus]);

        return response()->json(['message' => 'อัปเดตสถานะคำสั่งซื้อสำเร็จ', 'order' => $order]);
    }


    public function listOrders()
    {
        $user = auth()->user();
        $orders = orders::with(['orderItems.product.shop']) // ดึงข้อมูลร้านค้าด้วย
            ->where('users_user_id', $user->user_id)
            ->get();

        return response()->json($orders);
    }

    public function show($id)
    {
        $user = auth()->user();
        $order = orders::with('orderItems.product')
            ->where('order_id', $id)
            ->where('users_user_id', $user->user_id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'created_at' => $order->created_at,
            'items' => $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price
                ];
            })
        ]);
    }


    // ฟังก์ชันสำหรับผู้ขายอัปเดตสถานะคำสั่งซื้อ เช่น ยืนยันการชำระเงินหรือจัดส่งสินค้า
    public function updateStatus(Request $request, $id)
    {
        // ควรตรวจสอบสิทธิ์ของผู้ใช้ (เช่น ตรวจสอบว่าเป็นผู้ขายของร้านที่เกี่ยวข้อง)
        $order = orders::findOrFail($id);
        $newStatus = $request->input('status');

        // อัปเดตสถานะ (คุณสามารถตรวจสอบความถูกต้องของ status เพิ่มเติมได้)
        $order->update(['status' => $newStatus]);

        // ส่งการแจ้งเตือนให้ลูกค้าทราบ (สามารถใช้ Event, Notification ใน Laravel)
        return response()->json([
            'message' => 'อัปเดตสถานะคำสั่งซื้อเรียบร้อยแล้ว',
            'order' => $order,
        ]);
    }

    // สำหรับผู้ขาย: ดูคำสั่งซื้อที่เกี่ยวข้องกับร้านของตนเอง
    public function sellerOrders()
    {
        $user = auth()->user();
        $shop = $user->shop;

        if (!$shop) {
            return response()->json(['error' => 'ไม่พบร้านของคุณ'], 404);
        }

        $orders = orders::where('shops_shop_id', $shop->shop_id)
            ->with('orderItems.product')
            ->get();

        return response()->json($orders);
    }

    public function getOrdersByStatus($status)
    {
        $user = auth()->user();

        $orders = orders::with(['orderItems.product.shop'])
            ->where('users_user_id', $user->user_id)
            ->where('status', $status)
            ->get();

        return response()->json($orders);
    }


}
