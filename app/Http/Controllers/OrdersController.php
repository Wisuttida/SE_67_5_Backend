<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cart;
use App\Models\orders;
use App\Models\order_items;
use App\Models\products;
class OrdersController extends Controller
{
    public function checkout(Request $request)
    {
        // รับข้อมูลผู้ใช้ที่เข้าสู่ระบบ (auth)
        $user = auth()->user();

        // ดึงตะกร้าของผู้ใช้ พร้อมกับรายการสินค้า (cartItems) และข้อมูลสินค้า (product)
        $cart = Cart::with('cartItems.product')
            ->where('users_user_id', $user->user_id)
            ->first();

        if (!$cart) {
            return response()->json(['error' => 'ไม่พบตะกร้า'], 404);
        }

        $cartItems = $cart->cartItems;
        $stockErrors = [];

        // ตรวจสอบ Stock ของสินค้าแต่ละชิ้นในตะกร้า
        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock_quantity) {
                $stockErrors[] = "สินค้า {$item->product->name} มีจำนวนในสต็อกไม่เพียงพอ";
            }
        }

        if (count($stockErrors) > 0) {
            return response()->json(['errors' => $stockErrors], 400);
        }

        // แยกกลุ่มรายการสินค้าในตะกร้าตามร้าน (shops_shop_id)
        $groupedItems = $cartItems->groupBy(function ($item) {
            return $item->product->shops_shop_id;
        });

        $orders = [];

        foreach ($groupedItems as $shopId => $items) {
            // คำนวณราคารวมของสินค้าในกลุ่มนี้
            $totalAmount = $items->sum(function ($item) {
                return $item->quantity * $item->price;
            });

            // สร้างคำสั่งซื้อใหม่
            // สมมุติว่า client ส่ง address_id มาด้วยใน request
            $order = orders::create([
                'total_amount' => $totalAmount,
                'status' => 'pending', // เริ่มต้นเป็น 'รอการชำระเงิน'
                'addresses_address_id' => $request->input('address_id'),
                'shops_shop_id' => $shopId,
                'users_user_id' => $user->user_id,
            ]);

            // สร้างรายการคำสั่งซื้อสำหรับแต่ละสินค้า
            foreach ($items as $item) {
                order_items::create([
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'products_product_id' => $item->product->product_id,
                    'orders_order_id' => $order->order_id,
                ]);
            }

            $orders[] = $order;
        }

        // ล้างรายการสินค้าในตะกร้าหลัง Checkout
        $cart->cartItems()->delete();

        return response()->json([
            'message' => 'สร้างคำสั่งซื้อเรียบร้อยแล้ว',
            'orders' => $orders,
        ], 201);
    }

    // ฟังก์ชันสำหรับลูกค้าดูรายการคำสั่งซื้อของตนเอง
    public function listOrders()
    {
        $user = auth()->user();
        $orders = orders::where('users_user_id', $user->user_id)->get();
        return response()->json($orders);
    }

    // ฟังก์ชันสำหรับลูกค้าดูรายละเอียดคำสั่งซื้อ
    public function show($id)
    {
        $user = auth()->user();
        $order = orders::with('orderItems.product')->where('order_id', $id)
            ->where('users_user_id', $user->user_id)
            ->first();
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }
        return response()->json($order);
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
        // สมมุติว่าผู้ขายมีความสัมพันธ์กับร้านของตน (shop)
        $shop = $user->shop;
        if (!$shop) {
            return response()->json(['error' => 'ไม่พบร้านของคุณ'], 404);
        }
        $orders = orders::where('shops_shop_id', $shop->shop_id)->get();
        return response()->json($orders);
    }
}
