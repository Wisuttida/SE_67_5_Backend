<?php

namespace App\Http\Controllers;

use App\Models\products;
use App\Models\shops;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        // สร้าง query หลัก
        $query = \App\Models\products::query();

        // 1) ค้นหาตามชื่อสินค้า (เช่น ถ้า user กรอกผ่าน query param ?search=xxx)
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // 2) ค้นหาตาม gender_target (ผู้ใช้สามารถส่งเป็น array หลายค่าได้ เช่น ?gender_target[]=Male&gender_target[]=Unisex)
        if ($request->has('gender_target')) {
            // gender_target อาจมาเป็น array
            $genders = $request->get('gender_target'); // สมมติเป็น array
            $query->whereIn('gender_target', $genders);
        }

        // 3) ค้นหาตาม fragrance_strength (อาจมาเป็น array เช่นกัน)
        if ($request->has('fragrance_strength')) {
            $strengths = $request->get('fragrance_strength'); // array
            $query->whereIn('fragrance_strength', $strengths);
        }

        // 4) ค้นหาตาม fragrance_tone (อาจมาเป็น array เช่นกัน)
        //    เราจะใช้ whereHas() เพื่อกรองสินค้าที่มี fragrance tone ตามที่เลือก
        if ($request->has('fragrance_tone')) {
            $tones = $request->get('fragrance_tone'); // array
            $query->whereHas('fragranceTones', function ($q) use ($tones) {
                $q->whereIn('fragrance_tone.fragrance_tone_id', $tones);
            });
        }

        // ดึงข้อมูลร้านค้าพร้อมกับสินค้า
        $query->with('shop');

        // อย่าลืม select คอลัมน์ shops_shop_id ด้วย เพื่อให้ความสัมพันธ์ทำงานได้
        $products = $query->select([
            'product_id',
            'name',
            'price',
            'image_url',
            'gender_target',
            'volume',
            'shops_shop_id',
            'fragrance_strength',
        ])->get();

        // map ข้อมูลสินค้าให้รวมข้อมูลจากร้านค้า (ชื่อร้านและรูปร้านค้า)
        $products = $products->map(function ($product) {
            return [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'price' => $product->price,
                'image_url' => $product->image_url,
                'gender_target' => $product->gender_target,
                'volume' => $product->volume,
                'shop_name' => optional($product->shop)->shop_name,
                'shop_image' => optional($product->shop)->shop_image, // สมมติว่ามีคอลัมน์ shop_image ในตาราง shops
                'fragrance_strength' => $product->fragrance_strength,
            ];
        });

        return response()->json($products);
    }

    public function show($id)
    {
        // ดึงสินค้าที่ต้องการ พร้อมโหลด shop และ fragranceTones
        $product = \App\Models\products::with(['shop', 'fragranceTones'])
            ->findOrFail($id);

        // สร้าง response ในรูปแบบที่ต้องการ
        // เช่น แสดงชื่อร้านค้า, รายละเอียดสินค้า
        $response = [
            'product_id' => $product->product_id,
            'name' => $product->name,
            'price' => $product->price,
            'image_url' => $product->image_url,
            'volume' => $product->volume,
            'shop_name' => optional($product->shop)->shop_name,  // กันกรณี shop เป็น null
            'shop_image' => optional($product->shop)->shop_image,
            'description' => $product->description,
            // อื่น ๆ ที่ต้องการ
            'fragrance_strength' => $product->fragrance_strength,
            'gender_target' => $product->gender_target,
            'status' => $product->status,
            // รายการ fragrance_tone ทั้งหมด
            'fragrance_tones' => $product->fragranceTones->map(function ($tone) {
                return [
                    'fragrance_tone_id' => $tone->fragrance_tone_id,
                    'fragrance_tone_name' => $tone->fragrance_tone_name
                ];
            })
        ];

        return response()->json($response);
    }

    public function latestProducts()
    {
        // ดึงเฉพาะคอลัมน์ที่ต้องการ: id,image_url, name, price
        // เรียงจากสินค้าที่เพิ่มเข้ามาล่าสุด (created_at DESC)
        $products = \App\Models\products::select('product_id', 'image_url', 'name', 'price')
            ->orderBy('created_at', 'desc')
            ->get();

        // ส่งข้อมูลไปยัง view (หรือส่งเป็น JSON ถ้าเป็น API)
        return response()->json($products);
    }

    public function store(Request $request)
    {
        // ดึงข้อมูลของผู้ใช้ที่เข้าสู่ระบบ
        $user = auth()->user();
        //สำหรับทดสอบระบบ
        //$user = \App\Models\User::find(2); // สมมติว่า user_id 2 คือผู้ใช้ที่เราต้องการทดสอบ

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
