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
            'payment_proof_url' => $path,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'อัปโหลดหลักฐานการชำระเงินสำเร็จ', 'payment' => $payment]);
    }

    public function verifyPayment($payment_id)
    {
        $payment = payments::find($payment_id);
        if (!$payment) {
            return response()->json(['error' => 'ไม่พบรายการชำระเงิน'], 404);
        }

        $payment->update(['status' => 'completed']);
        $payment->order->update(['status' => 'confirmed']);

        return response()->json(['message' => 'ยืนยันการชำระเงินสำเร็จ']);
    }
    
}
