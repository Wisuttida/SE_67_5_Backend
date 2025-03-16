<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sales_offers;
use App\Models\buy_post;
use App\Models\orders;
use Illuminate\Support\Facades\Auth;


class SalesOfferController extends Controller
{
    // ฟังก์ชันให้เกษตรกรส่งข้อเสนอ (offer) ตอบโพสต์รับซื้อวัตถุดิบ
    public function storeOffer(Request $request, $buyPostId)
    {
        // ตรวจสอบว่า user เป็นเจ้าของฟาร์ม (position_id = 2)
        $user = Auth::user();
        if ($user->role->position_position_id != 2) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยื่นข้อเสนอในโพสต์รับซื้อวัตถุดิบ'], 403);
        }

        // Validate ข้อมูลที่จำเป็น
        $validated = $request->validate([
            'quantity'       => 'required|numeric',
            'price_per_unit' => 'required|numeric',
        ]);

        // ค้นหาโพสต์รับซื้อที่ต้องการตอบกลับ
        $buyPost = buy_post::find($buyPostId);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์รับซื้อวัตถุดิบที่ระบุ'], 404);
        }

        // สร้าง record สำหรับ sales offer
        $offer = new sales_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $validated['price_per_unit'];
        $offer->status = 'pending'; // เริ่มต้นเป็น pending
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์รับซื้อไหน (sales_offers เชื่อมกับ buy_post ผ่าน sales_post_post_id)
        $offer->sales_post_post_id = $buyPost->post_id;
        $offer->save();

        return response()->json(['message' => 'ส่งข้อเสนอเรียบร้อยแล้ว', 'offer' => $offer]);
    }

    // ฟังก์ชันให้ผู้ประกอบการยืนยันข้อเสนอของเกษตรกร
    public function confirmOffer($offerId)
    {
        $user = Auth::user();
        if ($user->role->position_position_id != 1) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยืนยันข้อเสนอ'], 403);
        }

        $offer = sales_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }

        if ($offer->status != 'pending') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ pending'], 400);
        }

        // เปลี่ยนสถานะของ offer เป็น confirmed
        $offer->status = 'confirmed';
        $offer->save();

        // เมื่อยืนยัน offer แล้ว ให้สร้างคำสั่งซื้อ (order)
        // คำนวณยอดเงินรวม = quantity * price_per_unit
        $totalAmount = $offer->quantity * $offer->price_per_unit;

        // ดึง default address ของผู้ประกอบการ (buyer)
        $defaultAddress = $user->addresses()->where('is_default', 1)->first();

        $order = new orders();
        $order->total_amount = $totalAmount;
        $order->status = 'pending';
        $order->addresses_address_id = $defaultAddress ? $defaultAddress->address_id : null;
        $order->shops_shop_id = $user->shop->shop_id;
        $order->users_user_id = $user->user_id;
        $order->save();

        return response()->json([
            'message' => 'ข้อเสนอได้รับการยืนยันและสร้างคำสั่งซื้อแล้ว',
            'offer'   => $offer,
            'order'   => $order
        ]);
    }

    // ฟังก์ชันให้ผู้ประกอบการปฏิเสธข้อเสนอของเกษตรกร
    public function rejectOffer($offerId)
    {
        $user = Auth::user();
        if ($user->role->position_position_id != 1) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ปฏิเสธข้อเสนอ'], 403);
        }

        $offer = sales_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }

        if ($offer->status != 'pending') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ pending'], 400);
        }

        $offer->status = 'rejected';
        $offer->save();

        return response()->json(['message' => 'ข้อเสนอถูกปฏิเสธ', 'offer' => $offer]);
    }
}
