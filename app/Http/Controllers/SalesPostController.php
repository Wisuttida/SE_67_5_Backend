<?php

namespace App\Http\Controllers;

use App\Models\ingredients;
use App\Models\sales_post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SalesPostController extends Controller
{
    // 2.1 แสดงรายการโพสต์ขายทั้งหมด
    public function index()
    {
        $salesPosts = sales_post::with('ingredients')->get();
        return response()->json(['sales_posts' => $salesPosts]);
    }
    // Controller function to show all Sales Posts
    public function showSalesPosts()
    {
        $salesPosts = sales_post::with('farm', 'ingredients')
            ->where('status', 'active')
            ->get();

        $salesPostsData = $salesPosts->map(function ($salesPost) {
            return [
                'post_id' => $salesPost->post_id,
                'farm_id' => $salesPost->farm ? $salesPost->farm->farm_id : null,
                'farm_image' => $salesPost->farm ? $salesPost->farm->farm_image : null,  // รูปภาพฟาร์ม
                'farm_name' => $salesPost->farm ? $salesPost->farm->farm_name : 'ไม่มีชื่อฟาร์ม', // ชื่อฟาร์ม
                'description' => $salesPost->description, // รายละเอียดโพสต์
                'price_per_unit' => $salesPost->price_per_unit, // ราคาต่อหน่วย
                'amount' => $salesPost->amount, // จำนวน
                'unit' => $salesPost->unit, // หน่วย
                'ingredient_name' => $salesPost->ingredients ? $salesPost->ingredients->name : 'ไม่มีข้อมูลวัตถุดิบ', // ชื่อวัตถุดิบ
                'status' => $salesPost->status, // สถานะ
                'sold_amount' => $salesPost->sold_amount, // จำนวนที่ขายไปแล้ว
            ];
        });

        return response()->json(['sales_posts' => $salesPostsData]);
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
        // ตรวจสอบว่า user เป็นเกษตรกร
        $role = $user->roles->firstWhere('position_position_id', 3);
        if (!$role) {
            return response()->json(['error' => 'คุณไม่มีสิทธิ์ดำเนินการนี้'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ingredients_id' => 'required|exists:ingredients,ingredient_id',
            'description' => 'required|string',
            'price_per_unit' => 'required|numeric',
            'amount' => 'required|numeric',
            'unit' => 'required|in:kg,t,mL,L',
            //'image' => 'required|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // อัปโหลดรูปภาพ
        //$imagePath = $request->file('image')->store('sales_posts', 'public');

        // ตรวจสอบว่าผู้ใช้งานนี้มี farm_id และใช้ความสัมพันธ์ farm ในการบันทึกโพสต์ขาย
        $farm = $user->farm;  // การเชื่อมโยงฟาร์มกับผู้ใช้
        if (!$farm) {
            return response()->json(['error' => 'ผู้ใช้งานนี้ไม่พบฟาร์ม'], 400);
        }

        // สร้างโพสต์ขาย
        $salesPost = sales_post::create([
            'ingredients_ingredient_id' => $request->ingredients_id,
            'description' => $request->description,
            'price_per_unit' => $request->price_per_unit,
            'amount' => $request->amount,
            'unit' => $request->unit,
            'farms_farm_id' => $farm->farm_id,  // ระบุ farms_farm_id ที่ถูกต้อง
            'status' => 'active',
            'times' => now(),
            //'image_url' => $imagePath
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

        $validator = Validator::make($request->all(), [
            'ingredients_id' => 'sometimes|exists:ingredients,ingredient_id',
            'description' => 'sometimes|string',
            'price_per_unit' => 'sometimes|numeric',
            'amount' => 'sometimes|numeric',
            'unit' => 'sometimes|in:kg,t,mL,L',
            //'image' => 'sometimes|image|mimes:jpg,png,jpeg|max:2048'
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
        // if ($request->hasFile('image')) {
        //     // สามารถลบรูปเก่าก่อนอัปโหลดใหม่ได้ (ถ้าต้องการ)
        //     $imagePath = $request->file('image')->store('sales_posts', 'public');
        //     $salesPost->image_url = $imagePath;
        // }
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
        $salesPost->delete();
        return response()->json(['message' => 'ลบโพสต์ขายสำเร็จ']);
    }
}
