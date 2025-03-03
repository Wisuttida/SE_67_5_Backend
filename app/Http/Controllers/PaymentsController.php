<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\orders;
use App\Models\payments;
use Illuminate\Support\Facades\Storage;
class PaymentsController extends Controller
{
    public function uploadPaymentProof(Request $request, $orderId)
    {
        // ตรวจสอบข้อมูล input
        $request->validate([
            'payment_proof' => 'required|image|max:2048', // จำกัดขนาดไฟล์และชนิดเป็น image
        ]);

        $user = auth()->user();

        // ตรวจสอบว่าคำสั่งซื้อที่ต้องการอัปโหลดหลักฐานเป็นของผู้ใช้
        $order = orders::where('order_id', $orderId)
            ->where('users_user_id', $user->user_id)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อหรือคุณไม่มีสิทธิ์'], 404);
        }

        // อัปโหลดไฟล์หลักฐานการชำระเงิน
        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        // สร้างเรคคอร์ดในตาราง payments
        $payment = payments::create([
            'amount' => $order->total_amount,
            'payment_proof_url' => Storage::url($path),
            'status' => 'pending', // รอการตรวจสอบจากผู้ขาย
            'orders_order_id' => $order->order_id,
        ]);

        return response()->json([
            'message' => 'อัปโหลดหลักฐานการชำระเงินเรียบร้อยแล้ว รอการตรวจสอบ',
            'payment' => $payment,
        ]);
    }
}
