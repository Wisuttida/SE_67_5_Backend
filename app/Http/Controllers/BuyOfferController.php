<?php

namespace App\Http\Controllers;
use App\Models\sales_post;
use App\Models\sales_offers;
use App\Models\buy_post;
use App\Models\buy_offers;
use App\Models\orders;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


class BuyOfferController extends Controller
{
    // ฟังก์ชันให้เกษตรกรส่งข้อเสนอ (offer) ตอบโพสต์รับซื้อวัตถุดิบ
    public function storeOffer(Request $request, $buyPostId)
    {
        // ตรวจสอบว่า user เป็นผู้ประกอบการ (position_id = 2)
        $user = Auth::user();
        $role = $user->roles->firstWhere('position_position_id', 2);  // ใช้ 2 แทน 1 หากเป็นผู้ประกอบการ
        if (!$role) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric',
            'price_per_unit' => 'required|numeric',
        ]);

        // ค้นหาโพสต์ขายวัตถุดิบที่ระบุ
        $salesPost = sales_post::find($buyPostId);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขายวัตถุดิบที่ระบุ'], 404);
        }

        $offer = new buy_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $validated['price_per_unit'];
        $offer->status = 'pending'; // เริ่มต้นเป็น pending
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์ขายไหน
        $offer->buy_post_post_id = $buyPostId->post_id;
        // เก็บ farm_id ที่เชื่อมโยงกับผู้ใช้
        $offer->farms_farm_id = $user->farm ? $user->farm->farm_id : null;  // เก็บ farm_id จากฟาร์มที่ผู้ใช้เชื่อมโยง
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
            'offer' => $offer,
            'order' => $order
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
