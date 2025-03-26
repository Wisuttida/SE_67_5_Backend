<?php

namespace App\Http\Controllers;

use App\Models\buy_post;
use App\Models\buy_offers;
use App\Models\orders;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


class BuyOfferController extends Controller
{

    //ดู details offers แต่ละ offers
    public function showOfferDetails($offerId)
    {
        // ดึงข้อมูลข้อเสนอจากตาราง buy_offers
        $offer = buy_offers::find($offerId);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }

        // ดึงข้อมูลฟาร์มจากตาราง farms โดยใช้ farms_farm_id ที่บันทึกไว้ในข้อเสนอ
        $farm = \App\Models\farms::find($offer->farms_farm_id);
        if (!$farm) {
            return response()->json(['error' => 'ไม่พบข้อมูลฟาร์มที่เกี่ยวข้อง'], 404);
        }

        // สร้าง response โดยรวมข้อมูลข้อเสนอและข้อมูลฟาร์มที่ต้องการ
        return response()->json([
            'offer' => $offer,
            'farm' => [
                'farm_image' => $farm->farm_image,
                'farm_name' => $farm->farm_name,
                'bank_name' => $farm->bank_name,
                'bank_account' => $farm->bank_account,
                'bank_number' => $farm->bank_number,
            ]
        ]);
    }


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
        $buyPost = buy_post::find($buyPostId);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขายวัตถุดิบที่ระบุ'], 404);
        }

        $offer = new buy_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $validated['price_per_unit'];
        $offer->status = 'submit'; // เริ่มต้นเป็น submit
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์ขายไหน
        $offer->buy_post_post_id = $buyPost->post_id;

        // เก็บ farm_id ที่เชื่อมโยงกับผู้ใช้
        $offer->farms_farm_id = $user->farm ? $user->farm->farm_id : null;  // เก็บ farm_id จากฟาร์มที่ผู้ใช้เชื่อมโยง
        $offer->save();

        return response()->json(['message' => 'ส่งข้อเสนอเรียบร้อยแล้ว', 'offer' => $offer]);
    }


    // ฟังก์ชันให้ผู้ประกอบการยืนยันข้อเสนอของเกษตรกร
    public function confirmOffer($offerId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'คุณต้องล็อกอินก่อน'], 401);
        }
        // ตรวจสอบให้แน่ใจว่า user เป็นผู้ประกอบการ (position_id = 2)
        $role = $user->roles->firstWhere('position_position_id', 2);
        if (!$role) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ยืนยันข้อเสนอ'], 403);
        }

        // ค้นหาข้อเสนอในตาราง buy_offers
        $offer = buy_offers::where('buy_offers_id', $offerId)->first();
        $buyPost = buy_post::find($offer->buy_post_post_id);
        if (!$offer) {
            return response()->json(['error' => 'ไม่พบข้อเสนอ'], 404);
        }
        if ($offer->status != 'submit') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ submit'], 400);
        }
        // ตรวจสอบว่า offer นี้เกี่ยวข้องกับฟาร์มของเกษตรกรที่ส่ง offer หรือไม่
        if ($buyPost->shops_shop_id != $user->shop->shop_id) {
            return response()->json(['error' => 'ข้อเสนอนี้ไม่เกี่ยวข้องกับร้านค้าของคุณ'], 403);
        }

        // เปลี่ยนสถานะเป็น confirmed
        $offer->status = 'confirmed';
        $offer->save();

        // คำนวณยอดรวม (total) จาก quantity กับ price_per_unit
        $totalAmount = $offer->quantity * $offer->price_per_unit;

        // ดึงที่อยู่หลักของผู้ซื้อ (ผู้ประกอบการ)
        $buyer = $user;
        $defaultAddress = $buyer->addresses()->where('is_default', 1)->first();
        if (!$defaultAddress) {
            return response()->json(['error' => 'ผู้ประกอบการไม่มีที่อยู่หลัก'], 400);
        }

        // สร้าง Ingredient Order ใหม่
        $ingredientOrder = new \App\Models\ingredient_orders();
        $ingredientOrder->total = $totalAmount;
        $ingredientOrder->status = 'pending';
        $ingredientOrder->farms_farm_id = $offer->farms_farm_id; // ผู้ขาย: ฟาร์มของเกษตรกร
        $ingredientOrder->shops_shop_id = $buyer->shop->shop_id;    // ผู้ซื้อ: ร้านของผู้ประกอบการ
        $ingredientOrder->addresses_address_id = $defaultAddress->address_id;
        // ระบุความสัมพันธ์กับ buy offer (ในกรณีนี้ sales offer จะเป็น null)
        $ingredientOrder->buy_offers_buy_offers_id = $offer->buy_offers_id;
        $ingredientOrder->sales_offers_sales_offers_id = null;
        $ingredientOrder->save();

        return response()->json([
            'message' => 'ข้อเสนอได้รับการยืนยันและสร้าง Ingredient Order แล้ว',
            'offer' => $offer,
            'ingredient_order' => $ingredientOrder,
        ]);
    }

    // ฟังก์ชันให้ผู้ประกอบการปฏิเสธข้อเสนอของเกษตรกร
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

        if ($offer->status != 'submit') {
            return response()->json(['error' => 'ข้อเสนอไม่อยู่ในสถานะ submit'], 400);
        }

        $offer->status = 'rejected';
        $offer->save();

        return response()->json(['message' => 'ข้อเสนอถูกปฏิเสธ', 'offer' => $offer]);
    }

}
