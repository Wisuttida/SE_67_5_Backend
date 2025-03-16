<?php

namespace App\Http\Controllers;

use App\Models\sales_post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SalesPostController extends Controller
{
    // 2.1 แสดงรายการโพสต์ขายทั้งหมด
    public function index()
    {
        $salesPosts = sales_post::all();
        return response()->json(['sales_posts' => $salesPosts]);
    }

    // 2.2 แสดงรายละเอียดโพสต์ขายแต่ละรายการ
    public function show($id)
    {
        $salesPost = sales_post::find($id);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขาย'], 404);
        }
        return response()->json(['sales_post' => $salesPost]);
    }

    // 2.3 สร้างโพสต์ขายใหม่ (เฉพาะเกษตรกร)
    public function store(Request $request)
    {
        $user = Auth::user();
        // ตรวจสอบ role: สมมติว่าเกษตรกรมี position_id เท่ากับ 2
        if ($user->role->position_position_id != 2) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ingredients_id' => 'required|exists:ingredients,ingredient_id',
            'description' => 'required|string',
            'price_per_unit' => 'required|numeric',
            'amount' => 'required|numeric',
            'unit' => 'required|in:kg,t,mL,L',
            'image' => 'required|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // อัปโหลดรูปภาพและเก็บ path โดยใช้ Storage (เก็บใน disk "public")
        $imagePath = $request->file('image')->store('sales_posts', 'public');

        $salesPost = sales_post::create([
            'ingredients_ingredient_id' => $request->ingredients_id,
            'description' => $request->description,
            'price_per_unit' => $request->price_per_unit,
            'amount' => $request->amount,
            'unit' => $request->unit,
            'farm_id' => $user->farm->farm_id,  // เกษตรกรมีความสัมพันธ์กับฟาร์ม
            'status' => 'active',
            'times' => now(),
            'image_url' => $imagePath
        ]);

        return response()->json(['message' => 'สร้างโพสต์ขายสำเร็จ', 'sales_post' => $salesPost]);
    }

    // 2.4 แก้ไขโพสต์ขาย (เฉพาะเจ้าของโพสต์)
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $salesPost = sales_post::find($id);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขาย'], 404);
        }
        // ตรวจสอบว่าโพสต์นี้เป็นของเกษตรกรที่ล็อกอินอยู่หรือไม่
        if ($salesPost->farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์แก้ไขโพสต์นี้'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ingredients_id' => 'sometimes|exists:ingredients,ingredient_id',
            'description' => 'sometimes|string',
            'price_per_unit' => 'sometimes|numeric',
            'amount' => 'sometimes|numeric',
            'unit' => 'sometimes|in:kg,t,mL,L',
            'image' => 'sometimes|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('ingredients_id')) {
            $salesPost->ingredients_ingredient_id = $request->ingredients_id;
        }
        if ($request->has('description')) {
            $salesPost->description = $request->description;
        }
        if ($request->has('price_per_unit')) {
            $salesPost->price_per_unit = $request->price_per_unit;
        }
        if ($request->has('amount')) {
            $salesPost->amount = $request->amount;
        }
        if ($request->has('unit')) {
            $salesPost->unit = $request->unit;
        }
        if ($request->hasFile('image')) {
            // สามารถลบรูปเก่าก่อนอัปโหลดใหม่ได้ (ถ้าต้องการ)
            $imagePath = $request->file('image')->store('sales_posts', 'public');
            $salesPost->image_url = $imagePath;
        }
        $salesPost->save();

        return response()->json(['message' => 'แก้ไขโพสต์ขายสำเร็จ', 'sales_post' => $salesPost]);
    }

    // 2.5 ลบโพสต์ขาย (เฉพาะเจ้าของโพสต์)
    public function destroy($id)
    {
        $user = Auth::user();
        $salesPost = sales_post::find($id);
        if (!$salesPost) {
            return response()->json(['error' => 'ไม่พบโพสต์ขาย'], 404);
        }
        if ($salesPost->farm_id != $user->farm->farm_id) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ลบโพสต์นี้'], 403);
        }
        $salesPost->delete();
        return response()->json(['message' => 'ลบโพสต์ขายสำเร็จ']);
    }
}
