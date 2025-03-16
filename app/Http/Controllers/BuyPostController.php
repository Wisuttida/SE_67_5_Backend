<?php

namespace App\Http\Controllers;

use App\Models\buy_post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class BuyPostController extends Controller
{
    // 1.1 แสดงรายการโพสต์รับซื้อทั้งหมด
    public function index()
    {
        // ดึงข้อมูลโพสต์รับซื้อทั้งหมด อาจเพิ่ม pagination ในภายหลัง
        $buyPosts = buy_post::all();
        return response()->json(['buy_posts' => $buyPosts]);
    }

    // 1.2 แสดงรายละเอียดของโพสต์รับซื้อแต่ละรายการ
    public function show($id)
    {
        $buyPost = buy_post::find($id);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์รับซื้อ'], 404);
        }
        return response()->json(['buy_post' => $buyPost]);
    }

    // 1.3 สร้างโพสต์รับซื้อใหม่ (เฉพาะผู้ประกอบการ)
    public function store(Request $request)
    {
        $user = Auth::user();
        // ตรวจสอบ role: สมมติว่าผู้ประกอบการมี position_id เท่ากับ 1
        if ($user->role->position_position_id != 1) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        // Validate input ที่จำเป็น
        $validator = Validator::make($request->all(), [
            'ingredients_id'  => 'required|exists:ingredients,ingredient_id',
            'description'     => 'required|string',
            'price_per_unit'  => 'required|numeric',
            'amount'          => 'required|numeric',
            'unit'            => 'required|in:kg,t,mL,L'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // สร้างโพสต์รับซื้อใหม่
        $buyPost = buy_post::create([
            'ingredients_ingredient_id' => $request->ingredients_id,
            'description'               => $request->description,
            'price_per_unit'            => $request->price_per_unit,
            'amount'                    => $request->amount,
            'unit'                      => $request->unit,
            'shops_shop_id'             => $user->shop->shop_id, // ผู้ประกอบการควรมีความสัมพันธ์กับร้านค้า
            'status'                    => 'active',
            'times'                     => now()
        ]);

        return response()->json(['message' => 'สร้างโพสต์รับซื้อสำเร็จ', 'buy_post' => $buyPost]);
    }

    // 1.4 แก้ไขโพสต์รับซื้อ (เฉพาะเจ้าของโพสต์)
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $buyPost = buy_post::find($id);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์รับซื้อ'], 404);
        }
        // ตรวจสอบว่าโพสต์นี้เป็นของผู้ประกอบการที่ล็อกอินอยู่หรือไม่
        if ($buyPost->shops_shop_id != $user->shop->shop_id) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์แก้ไขโพสต์นี้'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ingredients_id'  => 'sometimes|exists:ingredients,ingredient_id',
            'description'     => 'sometimes|string',
            'price_per_unit'  => 'sometimes|numeric',
            'amount'          => 'sometimes|numeric',
            'unit'            => 'sometimes|in:kg,t,mL,L'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // อัปเดตฟิลด์ที่มีการส่งเข้ามา
        if ($request->has('ingredients_id')) {
            $buyPost->ingredients_ingredient_id = $request->ingredients_id;
        }
        if ($request->has('description')) {
            $buyPost->description = $request->description;
        }
        if ($request->has('price_per_unit')) {
            $buyPost->price_per_unit = $request->price_per_unit;
        }
        if ($request->has('amount')) {
            $buyPost->amount = $request->amount;
        }
        if ($request->has('unit')) {
            $buyPost->unit = $request->unit;
        }
        $buyPost->save();

        return response()->json(['message' => 'แก้ไขโพสต์รับซื้อสำเร็จ', 'buy_post' => $buyPost]);
    }

    // 1.5 ลบโพสต์รับซื้อ (เฉพาะเจ้าของโพสต์)
    public function destroy($id)
    {
        $user = Auth::user();
        $buyPost = buy_post::find($id);
        if (!$buyPost) {
            return response()->json(['error' => 'ไม่พบโพสต์รับซื้อ'], 404);
        }
        if ($buyPost->shops_shop_id != $user->shop->shop_id) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้'], 403);
        }
        $buyPost->delete();
        return response()->json(['message' => 'ลบโพสต์รับซื้อสำเร็จ']);
    }
}
