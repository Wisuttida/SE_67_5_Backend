<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\orders;
use App\Models\payments;
use App\Models\custom_orders;
use App\Models\ingredient_orders;
use Illuminate\Support\Facades\Storage;

class PaymentsController extends Controller
{
    public function uploadPaymentProof(Request $request, $order_id)
    {
        $request->validate([
            'payment_proof' => 'required|string'
        ]);

        // ค้นหาคำสั่งซื้อจาก orders หรือ custom_orders
        $order = orders::find($order_id);
        if (!$order) {
            $order = custom_orders::find($order_id);
        }
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // ถ้าเป็นคำสั่งซื้อ custom orders ให้ตรวจสอบราคา
        if (get_class($order) === 'App\Models\custom_orders') {
            if ($order->is_tester === 'yes') {
                if (is_null($order->tester_price)) {
                    return response()->json(['error' => 'ยังไม่ระบุราคาของ tester กรุณาติดต่อร้านค้า'], 400);
                }
                $amount = $order->tester_price;
            } else {
                if (is_null($order->custom_order_price)) {
                    return response()->json(['error' => 'ยังไม่ระบุราคาคำสั่งซื้อ custom กรุณาติดต่อร้านค้า'], 400);
                }
                $amount = $order->custom_order_price;
            }
        } else {
            $amount = $order->total_amount;
        }

        // สร้าง payment โดยใช้ความสัมพันธ์ polymorphic
        $payment = $order->payment()->create([
            'amount' => $amount,
            'payment_proof_url' => $request->payment_proof,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'อัปโหลดหลักฐานการชำระเงินสำเร็จ', 'payment' => $payment]);
    }

    public function updatePaymentStatus(Request $request, $payment_id)
    {
        $request->validate([
            'status' => 'required|in:completed,failed'
        ]);

        $payment = payments::find($payment_id);
        if (!$payment) {
            return response()->json(['error' => 'ไม่พบรายการชำระเงิน'], 404);
        }

        // อัปเดตสถานะของ payment
        $payment->update(['status' => $request->status]);

        // หากสถานะเป็น completed ให้เปลี่ยนสถานะของ entity ที่เกี่ยวข้องผ่าน polymorphic relationship
        if ($request->status === 'completed' && $payment->paymentable) {
            $payment->paymentable->update(['status' => 'confirmed']);
        }

        return response()->json(['message' => 'อัปเดตสถานะการชำระเงินสำเร็จ', 'payment' => $payment]);
    }


    public function listPaymentsForShop(Request $request)
    {
        // สมมติว่าร้านได้รับการระบุผ่าน authentication
        $user = auth()->user();
        $shop = $user->shop; // ต้องมีความสัมพันธ์ระหว่าง user กับ shop

        if (!$shop) {
            return response()->json(['error' => 'ไม่พบร้านของคุณ'], 404);
        }

        // ดึงข้อมูล payments ที่มีคำสั่งซื้อหรือ custom order ของร้านนี้
        $payments = payments::with('paymentable')
            ->whereHas('paymentable', function ($query) use ($shop) {
                $query->where('shops_shop_id', $shop->shop_id);
            })->get();

        return response()->json(['payments' => $payments]);
    }
    public function uploadPaymentProofForIngredientOrder(Request $request, $ingredientOrderId)
    {
        $request->validate([
            'payment_proof' => 'required|string'
        ]);

        // ค้นหาคำสั่งซื้อจาก ingredient_orders
        $ingredientOrder = ingredient_orders::find($ingredientOrderId);
        if (!$ingredientOrder) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อวัตถุดิบ'], 404);
        }

        // สร้าง payment โดยใช้ความสัมพันธ์ polymorphic
        $payment = $ingredientOrder->payment()->create([
            'amount' => $ingredientOrder->total,
            'payment_proof_url' => $request->payment_proof,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'อัปโหลดหลักฐานการชำระเงินสำเร็จ', 'payment' => $payment]);
    }

    // ฟังก์ชันอัปเดตสถานะการชำระเงินสำหรับ ingredient_orders
    public function updatePaymentStatusForIngredientOrder(Request $request, $paymentId)
    {
        $request->validate([
            'status' => 'required|in:completed,failed'
        ]);

        $payment = payments::find($paymentId);
        if (!$payment) {
            return response()->json(['error' => 'ไม่พบรายการชำระเงิน'], 404);
        }

        // อัปเดตสถานะของ payment
        $payment->update(['status' => $request->status]);

        // หากสถานะเป็น completed ให้เปลี่ยนสถานะของ ingredient order
        if ($request->status === 'completed' && $payment->paymentable) {
            // เปลี่ยนสถานะของ ingredient order เป็น confirmed
            $ingredientOrder = $payment->paymentable;
            $ingredientOrder->status = 'confirmed';
            $ingredientOrder->save();
        }

        return response()->json(['message' => 'อัปเดตสถานะการชำระเงินสำเร็จ', 'payment' => $payment]);
    }
    // ฟังก์ชันดึงรายการการชำระเงินที่เกี่ยวข้องกับฟาร์ม
    public function listPaymentsForFarm(Request $request)
    {
        $user = auth()->user();
        $farm = $user->farm; // ตรวจสอบว่าผู้ใช้มีฟาร์มหรือไม่

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ดึงข้อมูล payments ที่เกี่ยวข้องกับฟาร์มจาก ingredient_orders เท่านั้น
        $payments = payments::with('paymentable')
            ->whereHas('paymentable', function ($query) use ($farm) {
                // ตรวจสอบว่า paymentable เป็น ingredient_orders และตรวจสอบ farm_id
                if ($query->getModel() instanceof \App\Models\ingredient_orders) {
                    $query->where('ingredient_orders.farms_farm_id', $farm->farm_id);
                }
            })
            ->get();

        return response()->json(['payments' => $payments]);
    }
}
