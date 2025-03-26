<?php

namespace App\Http\Controllers;

use App\Models\custom_orders;
use Illuminate\Http\Request;

class ShopCustomOrderController extends Controller
{
    public function getAcceptedShops()
    {
        // ดึงทุกร้านค้าที่มีสถานะ accept_custom = 1
        $shops = \App\Models\Shop::where('accept_custom', 1)
            ->get(['shop_image', 'shop_name']);

        // ตรวจสอบว่ามีร้านค้าอยู่หรือไม่
        if ($shops->isEmpty()) {
            return response()->json(['message' => 'ไม่พบร้านค้าที่ยอมรับการสั่งซื้อ'], 404);
        }

        // ส่งข้อมูลร้านค้าให้กับผู้ใช้
        return response()->json(['shops' => $shops]);
    }

    public function updateOrderStatus(Request $request, $order_id)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'custom_order_price' => 'required_if:action,accept|numeric|min:0',
            // ถ้า is_tester = 'yes' ต้องระบุ tester_price ด้วย
            'tester_price' => 'nullable|numeric|min:0',
        ]);

        $order = custom_orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // ตรวจสอบสิทธิ์ร้านค้า (เช่น เชื่อมกับ user ที่ login อยู่)
        $user = auth()->user();
        $shop = $user->shop; // ต้องมีความสัมพันธ์ระหว่าง user กับ shop
        if (!$shop || $shop->shop_id != $order->shops_shop_id) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์จัดการคำสั่งซื้อนี้'], 403);
        }

        if ($request->action === 'reject') {
            $order->update(['status' => 'reject orders']);
        } else if ($request->action === 'accept') {
            // สำหรับคำสั่งซื้อที่ไม่ใช่ tester
            if ($order->is_tester === 'no') {
                $order->update([
                    'custom_order_price' => $request->custom_order_price,
                    'status' => 'pending'
                ]);
            } else {
                // ถ้าเป็น tester ต้องมี tester_price ด้วย
                if (!$request->tester_price) {
                    return response()->json(['error' => 'กรุณาระบุราคา tester ด้วย'], 400);
                }
                $order->update([
                    'custom_order_price' => $request->custom_order_price,
                    'tester_price' => $request->tester_price,
                    'status' => 'pending'
                ]);
            }
        }

        return response()->json(['message' => 'อัปเดตสถานะคำสั่งซื้อสำเร็จ', 'order' => $order]);
    }

    // เมื่อตรวจสอบการชำระเงินเรียบร้อยแล้ว ให้เปลี่ยนสถานะเป็น preparing
    public function confirmPayment(Request $request, $order_id)
    {
        $order = custom_orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // สมมติว่ามีการตรวจสอบ payment แล้วว่าถูกต้อง
        $order->update(['status' => 'preparing']);
        return response()->json(['message' => 'ยืนยันการชำระเงินแล้ว', 'order' => $order]);
    }

    // ส่งสินค้าหรือ tester ให้ลูกค้า
    public function shipOrder(Request $request, $order_id)
    {
        $order = custom_orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        if ($order->is_tester === 'no') {
            $order->update(['status' => 'product shipping']);
        } else {
            $order->update(['status' => 'tester shipping']);
        }

        return response()->json(['message' => 'อัปเดตสถานะการจัดส่งสำเร็จ', 'order' => $order]);
    }
}
