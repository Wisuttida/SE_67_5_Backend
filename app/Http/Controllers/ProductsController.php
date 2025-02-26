<?php

namespace App\Http\Controllers;

use App\Models\products;
use App\Models\shops;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function store(Request $request)
    {
        // ดึงข้อมูลของผู้ใช้ที่เข้าสู่ระบบ
        // $user = auth()->user();
        //สำหรับทดสอบระบบ
        $user = \App\Models\User::find(2); // สมมติว่า user_id 2 คือผู้ใช้ที่เราต้องการทดสอบ

        // ตรวจสอบว่าผู้ใช้มีตำแหน่งที่ตรงกับความต้องการหรือไม่
        if (!$user->positions()->where('position_id', 2)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // ดึงข้อมูล shop ที่ผู้ใช้เป็นเจ้าของ
        $shop = $user->shop;
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }



        // Validate ข้อมูลสินค้าตามที่ต้องการ
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'volume' => 'required|integer',
            'stock_quantity' => 'required|integer',
            'image_url' => 'nullable|url',
            'gender_target' => 'required|in:Male,Female,Unisex',
            'fragrance_strength' => 'required|in:Extrait de Parfum,Eau de Parfum (EDP),Eau de Toilette (EDT), Eau de Cologne (EDC),Mist',
            'status' => 'required|in:active,inactive,sold',
            // หากมีการเลือก fragrance tones
            'fragrance_tone_ids' => 'nullable|array',
            'fragrance_tone_ids.*' => 'integer|exists:fragrance_tone,fragrance_tone_id',
        ]);

        // รวมข้อมูล validated กับ shop id ที่ได้มา
        $productData = array_merge($validatedData, [
            'shops_shop_id' => $shop->shop_id,
        ]);

        // สร้างสินค้าใหม่
        $product = products::create($productData);

        // ถ้ามี fragrance tones ที่เลือก ให้แนบข้อมูลผ่านความสัมพันธ์ many-to-many
        if (!empty($validatedData['fragrance_tone_ids'])) {
            $product->fragranceTones()->attach($validatedData['fragrance_tone_ids']);
        }

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load('fragranceTones'),
        ], 201);
    }
    // อัปเดตสินค้า
    public function update(Request $request, $id)
    {
        // ค้นหาสินค้าตาม product_id
        $product = products::findOrFail($id);

        // อัปเดตข้อมูลสินค้า (เฉพาะ field ที่ต้องการ)
        $product->update($request->only([
            'name',
            'description',
            'price',
            'volume',
            'stock_quantity',
            'image_url',
            'gender_target',
            'fragrance_strength',
            'status',
            'shops_shop_id'
        ]));

        // ตรวจสอบว่ามีการส่ง fragrance_tone_ids เข้ามาหรือไม่ (ในรูปแบบ array ของ id)
        if ($request->has('fragrance_tone_ids')) {
            // sync() จะปรับปรุง pivot table ให้ตรงกับ fragrance_tone_ids ที่ส่งมา
            $product->fragranceTones()->sync($request->input('fragrance_tone_ids'));
        }

        return response()->json($product, 200);
    }



    // ลบสินค้า พร้อมกับ ON DELETE CASCADE
    public function destroy($id)
    {
        $product = products::findOrFail($id);
        $product->delete(); // เมื่อใช้ delete() ระบบจะส่งคำสั่ง DELETE ไปยัง DB และหาก foreign key ถูกตั้งค่า ON DELETE CASCADE ข้อมูลที่เกี่ยวข้องจะถูกลบไปด้วย
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
