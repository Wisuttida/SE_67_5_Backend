<?php

namespace App\Http\Controllers;

use App\Models\buy_post;
use App\Models\buy_offers;
use App\Models\orders;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BuyOfferController extends Controller
{

    public function showOffersByUserPosts()
    {
        $user = auth()->user(); // ดึงข้อมูลผู้ใช้ที่ล็อกอินอยู่

        // ดึงข้อมูลโพสต์ทั้งหมดที่สร้างโดยผู้ใช้
        $posts = buy_post::where('shops_shop_id', $user->shop->shop_id) // ใช้ shop_id ของผู้ใช้เพื่อดึงโพสต์ที่เกี่ยวข้อง
            ->get();

        // ตรวจสอบว่าโพสต์ที่ผู้ใช้สร้างมีหรือไม่
        if ($posts->isEmpty()) {
            return response()->json(['message' => 'คุณยังไม่มีโพสต์ที่สร้าง'], 404);
        }

        // ดึงข้อมูลข้อเสนอทั้งหมดที่ตอบกลับโพสต์
        $offers = buy_offers::whereIn('buy_post_post_id', $posts->pluck('post_id'))
            ->with('farm')  // เปลี่ยนจาก 'farms' เป็น 'farm'
            ->get();


        // ตรวจสอบว่ามีข้อเสนอที่ตอบกลับโพสต์เหล่านี้หรือไม่
        if ($offers->isEmpty()) {
            return response()->json(['message' => 'ยังไม่มีข้อเสนอที่ตอบกลับโพสต์ของคุณ'], 404);
        }

        // สร้าง response โดยรวมข้อมูลโพสต์และข้อเสนอที่เกี่ยวข้อง
        return response()->json([
            'posts' => $posts->map(function ($post) {
                return [
                    'post_id' => $post->post_id,
                    'description' => $post->description,
                    'status' => $post->status,
                    'price_per_unit' => $post->price_per_unit,
                    'amount' => $post->amount,
                    'unit' => $post->unit,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ];
            }),
            'offers' => $offers->map(function ($offer) {
                return [
                    'offer_id' => $offer->buy_offers_id,
                    'quantity' => $offer->quantity,
                    'price_per_unit' => $offer->price_per_unit,
                    'status' => $offer->status,
                    'created_at' => $offer->created_at,
                    'farm' => $offer->farm ? [
                        'farm_name' => $offer->farm->farm_name,
                        'farm_image' => $offer->farm->farm_image,
                        'bank_name' => $offer->farm->bank_name,
                        'bank_account' => $offer->farm->bank_account,
                        'bank_number' => $offer->farm->bank_number,
                    ] : null  // เพิ่มการตรวจสอบ null
                ];
            })
        ]);

    }

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
        Log::info('Received Request:', $request->all()); // Log ข้อมูลที่ส่งมาจาก frontend
        // ตรวจสอบว่า user เป็นผู้ประกอบการ (position_id = 2)
        $user = Auth::user();
        $role = $user->roles->firstWhere('position_position_id', 2);  // ใช้ 2 แทน 1 หากเป็นผู้ประกอบการ
        if (!$role) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        // ตรวจสอบและ validate ข้อมูลที่จำเป็น
        $validated = $request->validate([
            'quantity' => 'required|numeric',
            // price_per_unit ไม่จำเป็นต้องถูกส่ง ถ้าไม่ส่งให้ใช้ค่าจากโพสต์รับซื้อ
            'price_per_unit' => 'sometimes|numeric',
        ]);

        // ค้นหาโพสต์ขายวัตถุดิบที่ระบุ
        $buyPost = buy_post::find($buyPostId);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขายวัตถุดิบที่ระบุ'], 404);
        }

        // ตรวจสอบว่า จำนวนข้อเสนอ (quantity) ไม่เกินจำนวนที่เหลือจาก amount - sold_amount
        $remainingAmount = $buyPost->amount - $buyPost->sold_amount;  // คำนวณจำนวนที่ยังเหลือ

        if ($validated['quantity'] > $remainingAmount) {
            return response()->json(['error' => 'จำนวนข้อเสนอไม่สามารถเกินจำนวนที่ผู้ประกอบการต้องการได้'], 400);
        }

        // ถ้า price_per_unit ไม่ถูกส่งมา ให้ใช้ราคาจากโพสต์รับซื้อ
        $pricePerUnit = isset($validated['price_per_unit']) ? $validated['price_per_unit'] : $buyPost->price_per_unit;

        // สร้างข้อเสนอใหม่
        $offer = new buy_offers();
        $offer->quantity = $validated['quantity'];
        $offer->price_per_unit = $pricePerUnit;  // ใช้ราคาจากโพสต์รับซื้อหากไม่ได้ส่งมา
        $offer->status = 'submit'; // เริ่มต้นเป็น submit
        // เก็บข้อมูลว่า offer นี้ตอบโพสต์ขายไหน
        $offer->buy_post_post_id = $buyPost->post_id;

        // เก็บ farm_id ที่เชื่อมโยงกับผู้ใช้
        $offer->farms_farm_id = $user->farm ? $user->farm->farm_id : null;  // เก็บ farm_id จากฟาร์มที่ผู้ใช้เชื่อมโยง

        if ($offer->save()) {
            Log::info('Offer saved successfully:', $offer->toArray()); // Log ข้อมูลที่บันทึกแล้ว
            return response()->json(['message' => 'ส่งข้อเสนอเรียบร้อยแล้ว', 'offer' => $offer]);
        } else {
            Log::error('Failed to save offer');
            return response()->json(['error' => 'ไม่สามารถบันทึกข้อเสนอได้'], 500);
        }
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

        // สร้างการชำระเงิน (Payment) สำหรับ Ingredient Order
        $payment = new \App\Models\payments();
        $payment->amount = $totalAmount;
        $payment->paymentable_id = $ingredientOrder->ingredient_orders_id;
        $payment->paymentable_type = \App\Models\ingredient_orders::class;
        $payment->status = 'pending';  // สถานะการชำระเงินเป็น pending
        $payment->save();

        return response()->json([
            'message' => 'ข้อเสนอได้รับการยืนยันและสร้าง Ingredient Order พร้อมการชำระเงินแล้ว',
            'offer' => $offer,
            'ingredient_order' => $ingredientOrder,
            'payment' => $payment
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
