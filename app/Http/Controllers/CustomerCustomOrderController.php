<?php

namespace App\Http\Controllers;

use App\Models\custom_orders;
use Illuminate\Http\Request;

class CustomerCustomOrderController extends Controller
{
    // ลูกค้ายอมรับ tester ที่ได้รับ
    public function acceptTester(Request $request, $order_id)
    {
        $order = custom_orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // เมื่อยอมรับ tester ให้เปลี่ยนสถานะกลับไปรอการชำระเงิน (pending) สำหรับขั้นตอนถัดไป
        $order->update(['status' => 'pending']);

        return response()->json(['message' => 'คุณได้ยอมรับ tester แล้ว', 'order' => $order]);
    }

    // ลูกค้าปฏิเสธ tester และแก้ไขข้อมูลคำสั่งซื้อใหม่
    public function rejectTester(Request $request, $order_id)
    {
        $order = custom_orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        $request->validate([
            'fragrance_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'intensity_level' => 'required|integer|min:0|max:100',
            'volume_ml' => 'required|integer|min:1',
            'is_tester' => 'required|in:yes,no',
            'ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'ingredient_percentage' => 'required|integer|min:0|max:100',
        ]);

        // ลูกค้าปรับแก้ไขข้อมูลคำสั่งซื้อใหม่และสถานะเปลี่ยนกลับเป็น submit
        $order->update([
            'fragrance_name' => $request->fragrance_name,
            'description' => $request->description,
            'intensity_level' => $request->intensity_level,
            'volume_ml' => $request->volume_ml,
            'is_tester' => $request->is_tester,
            'status' => 'submit'
        ]);

        // อัปเดตรายละเอียดคำสั่งซื้อ (assume มี relation detail)
        $order->detail->update([
            'ingredients_ingredient_id' => $request->ingredient_id,
            'ingredient_percentage' => $request->ingredient_percentage,
        ]);

        return response()->json(['message' => 'แก้ไขคำสั่งซื้อเรียบร้อยและส่งคำสั่งซื้อใหม่', 'order' => $order]);
    }
}
