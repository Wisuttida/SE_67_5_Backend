<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\sales_offers;
use App\Models\buy_offers;
use App\Models\User;
use App\Models\sales_post;
use App\Models\buy_post;
use App\Models\orders;
use Illuminate\Support\Facades\Auth;


class SalesOfferController extends Controller
{
    // ฟังก์ชันให้ผู้ประกอบการส่งข้อเสนอในโพสต์ขายวัตถุดิบ
    public function storeOffer(Request $request, $salesPostId)
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
        $salesPost = sales_post::find($salesPostId);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขายวัตถุดิบที่ระบุ'], 404);
        }

        $offer = new sales_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $validated['price_per_unit'];
        $offer->status = 'submit'; // เริ่มต้นเป็น submit
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์ขายไหน
        $offer->sales_post_post_id = $salesPost->post_id;
        // เก็บ shop_id ที่เชื่อมโยงกับผู้ใช้
        $offer->shops_shop_id = $user->shop ? $user->shop->shop_id : null;
        // เก็บ shop_id จากฟาร์มที่ผู้ใช้เชื่อมโยง
        $offer->save();

        return response()->json(['message' => 'ส่งข้อเสนอเรียบร้อยแล้ว', 'offer' => $offer]);
    }

    // ฟังก์ชันให้เจ้าของฟาร์มยืนยันข้อเสนอจากผู้ประกอบการ
    public function confirmOffer($offerId)
    {
        $user = Auth::user();
        // ตรวจสอบให้แน่ใจว่า user เป็นเกษตรกร (position_id = 3)
        if ($user->role->position_position_id != 3) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยืนยันข้อเสนอ'], 403);
        }

        $offer = \App\Models\SalesOffer::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }
        if ($offer->status != 'submit') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ submit'], 400);
        }
        // ตรวจสอบว่า offer นี้เกี่ยวข้องกับฟาร์มของเกษตรกรที่ล็อกอินอยู่
        if ($offer->farms_farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'ข้อเสนอนี้ไม่เกี่ยวข้องกับฟาร์มของคุณ'], 403);
        }

        $offer->status = 'confirmed';
        $offer->save();

        // คำนวณยอดรวม (total)
        $totalAmount = $offer->quantity * $offer->price_per_unit;

        // ดึงข้อมูลผู้ซื้อ (ผู้ประกอบการ) จาก offer
        $buyer = \App\Models\User::find($offer->users_user_id);
        if (!$buyer) {
            return response()->json(['error' => 'ไม่พบข้อมูลผู้ซื้อ'], 404);
        }
        $defaultAddress = $buyer->addresses()->where('is_default', 1)->first();
        if (!$defaultAddress) {
            return response()->json(['error' => 'ผู้ซื้อไม่มีที่อยู่หลัก'], 400);
        }

        // สร้าง Ingredient Order ใหม่
        $ingredientOrder = new \App\Models\IngredientOrder();
        $ingredientOrder->total = $totalAmount;
        $ingredientOrder->status = 'pending';
        $ingredientOrder->farms_farm_id = $user->farm->farm_id;  // ผู้ขาย: ฟาร์มของเกษตรกรที่ยืนยัน
        $ingredientOrder->shops_shop_id = $buyer->shop->shop_id;     // ผู้ซื้อ: ร้านของผู้ประกอบการ
        $ingredientOrder->addresses_address_id = $defaultAddress->address_id;
        // ระบุความสัมพันธ์กับ sales offer (ในกรณีนี้ buy offer จะเป็น null)
        $ingredientOrder->sales_offers_sales_offers_id = $offer->sales_offers_id;
        $ingredientOrder->buy_offers_buy_offers_id = null;
        $ingredientOrder->save();

        return response()->json([
            'message' => 'ข้อเสนอได้รับการยืนยันและสร้าง Ingredient Order แล้ว',
            'offer' => $offer,
            'ingredient_order' => $ingredientOrder,
        ]);
    }

    // ฟังก์ชันให้เจ้าของฟาร์มปฏิเสธข้อเสนอจากผู้ประกอบการ
    public function rejectOffer($offerId)
    {
        $user = Auth::user();
        if ($user->role->position_position_id != 3) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ปฏิเสธข้อเสนอ'], 403);
        }

        $offer = sales_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }
        if ($offer->status != 'submit') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ submit'], 400);
        }

        if ($offer->farms_farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'ข้อเสนอไม่เกี่ยวข้องกับฟาร์มของคุณ'], 403);
        }

        $offer->status = 'rejected';
        $offer->save();

        return response()->json(['message' => 'ข้อเสนอถูกปฏิเสธ', 'offer' => $offer]);
    }

}
