<?php

namespace App\Http\Controllers;
use App\Models\sales_post;
use App\Models\buy_offers;
use App\Models\orders;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BuyOfferController extends Controller
{
    // ฟังก์ชันให้ผู้ประกอบการส่งข้อเสนอในโพสต์ขายวัตถุดิบ
    public function storeOffer(Request $request, $salesPostId)
    {
        // ตรวจสอบว่า user เป็นผู้ประกอบการ (position_id = 1)
        $user = Auth::user();
        if ($user->role->position_position_id != 1) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยื่นข้อเสนอในโพสต์ขายวัตถุดิบ'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
        ]);

        // ค้นหาโพสต์ขายวัตถุดิบที่ระบุ
        $salesPost = sales_post::find($salesPostId);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขายวัตถุดิบที่ระบุ'], 404);
        }

        $offer = new buy_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $validated['price_per_unit'];
        $offer->status = 'pending'; // เริ่มต้นเป็น pending
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์ขายไหน
        $offer->buy_post_post_id = $salesPost->post_id;
        // บันทึก farm id จากโพสต์ขาย (เพื่อใช้ตรวจสอบในการยืนยัน/ปฏิเสธ)
        $offer->farms_farm_id = $salesPost->farms_farm_id;
        // *** คำแนะนำ: อาจเพิ่มคอลัมน์ users_user_id ใน buy_offers เพื่อบันทึก user id ของผู้ประกอบการที่ส่ง offer ***
        $offer->users_user_id = $user->user_id;
        $offer->save();

        return response()->json(['message' => 'ส่งข้อเสนอเรียบร้อยแล้ว', 'offer' => $offer]);
    }

    // ฟังก์ชันให้เจ้าของฟาร์มยืนยันข้อเสนอจากผู้ประกอบการ
    public function confirmOffer($offerId)
    {
        $user = Auth::user();
        if ($user->role->position_position_id != 2) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยืนยันข้อเสนอ'], 403);
        }

        $offer = buy_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }
        if ($offer->status != 'pending') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ pending'], 400);
        }

        // ตรวจสอบว่า offer นี้เกี่ยวข้องกับฟาร์มของเจ้าของฟาร์มที่ล็อกอินอยู่
        if ($offer->farms_farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'ข้อเสนอนี้ไม่เกี่ยวข้องกับฟาร์มของคุณ'], 403);
        }

        $offer->status = 'confirmed';
        $offer->save();

        // สร้างคำสั่งซื้อ (order) เมื่อข้อเสนอได้รับการยืนยัน
        $totalAmount = $offer->quantity * $offer->price_per_unit;

        // ดึงข้อมูลผู้ประกอบการ (buyer) จาก offer โดยใช้ users_user_id
        $buyer = User::find($offer->users_user_id);
        $defaultAddress = $buyer->addresses()->where('is_default', 1)->first();

        $order = new orders();
        $order->total_amount = $totalAmount;
        $order->status = 'pending';
        $order->addresses_address_id = $defaultAddress ? $defaultAddress->address_id : null;
        $order->shops_shop_id = $buyer->shop->shop_id;
        $order->users_user_id = $buyer->user_id;
        $order->save();

        return response()->json([
            'message' => 'ข้อเสนอได้รับการยืนยันและสร้างคำสั่งซื้อแล้ว',
            'offer' => $offer,
            'order' => $order
        ]);
    }

    // ฟังก์ชันให้เจ้าของฟาร์มปฏิเสธข้อเสนอจากผู้ประกอบการ
    public function rejectOffer($offerId)
    {
        $user = Auth::user();
        if ($user->role->position_position_id != 2) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ปฏิเสธข้อเสนอ'], 403);
        }

        $offer = buy_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }
        if ($offer->status != 'pending') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ pending'], 400);
        }

        if ($offer->farms_farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'ข้อเสนอไม่เกี่ยวข้องกับฟาร์มของคุณ'], 403);
        }

        $offer->status = 'rejected';
        $offer->save();

        return response()->json(['message' => 'ข้อเสนอถูกปฏิเสธ', 'offer' => $offer]);
    }
}
