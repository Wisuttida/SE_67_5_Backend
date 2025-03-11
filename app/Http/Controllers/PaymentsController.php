<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\orders;
use App\Models\payments;
use Illuminate\Support\Facades\Storage;

class PaymentsController extends Controller
{
    public function uploadPaymentProof(Request $request, $order_id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        $order = orders::find($order_id);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        $path = $request->file('payment_proof')->store('payments', 'public');

        $payment = payments::create([
            'orders_order_id' => $order->order_id,
            'amount' => $order->total_amount,
            'payment_proof_url' => $path,  // เปลี่ยนจาก payment_proof_url เป็น image_url
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

        // อัปเดตสถานะของ payment ตามที่ผู้ประกอบการตรวจสอบแล้ว
        $payment->update(['status' => $request->status]);

        // หากตรวจสอบแล้วว่าถูกต้อง ให้เปลี่ยนสถานะของคำสั่งซื้อที่เกี่ยวข้องเป็น confirmed
        if ($request->status === 'completed') {
            $payment->order->update(['status' => 'confirmed']);
        }

        return response()->json(['message' => 'อัปเดตสถานะการชำระเงินสำเร็จ', 'payment' => $payment]);
    }


}
