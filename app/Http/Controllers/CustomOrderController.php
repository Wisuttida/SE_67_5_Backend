<?php
namespace App\Http\Controllers;

use App\Models\custom_orders;
use App\Models\shops;
use Illuminate\Http\Request;
use App\Models\custom_order_details;
use App\Models\Products;
use Illuminate\Support\Facades\Validator;

class CustomOrderController extends Controller
{
    // ลูกค้าส่งคำสั่งซื้อ custom order
    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,shop_id',
            'fragrance_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'intensity_level' => 'required|integer|min:0|max:100',
            'volume_ml' => 'required|integer|min:1',
            'is_tester' => 'required|in:yes,no',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'ingredients.*.ingredient_percentage' => 'required|integer|min:0|max:100',
        ]);


        // ตรวจสอบร้านค้าว่ายอมรับคำสั่ง custom หรือไม่
        $shop = shops::find($request->shop_id);
        if (!$shop || $shop->accepts_custom != 1) {
            return response()->json(['error' => 'ร้านค้านี้ไม่รับคำสั่งทำน้ำหอม custom'], 400);
        }

        // รับข้อมูลผู้ใช้และตรวจสอบที่อยู่เริ่มต้น
        $user = auth()->user();
        $defaultAddress = $user->addresses()->where('is_default', 1)->first();

        if (!$defaultAddress) {
            return response()->json(['error' => 'โปรดตั้งค่าที่อยู่หลักก่อนทำการสั่งซื้อ'], 400);
        }

        // สร้าง custom order ด้วยสถานะ submit พร้อมกับระบุ addresses_address_id
        // สร้าง custom order ด้วยสถานะ submit พร้อมกับระบุ addresses_address_id
        $customOrder = custom_orders::create([
            'fragrance_name' => $request->fragrance_name,
            'description' => $request->description,
            'intensity_level' => $request->intensity_level,
            'volume_ml' => $request->volume_ml,
            'is_tester' => $request->is_tester,
            'status' => 'submit',
            'shops_shop_id' => $shop->shop_id,
            'users_user_id' => $user->user_id,
            'addresses_address_id' => $defaultAddress->address_id,
        ]);

        // วนลูปผ่าน array ingredients เพื่อบันทึกรายละเอียดของ custom order
        foreach ($request->ingredients as $ingredient) {
            custom_order_details::create([
                'custom_orders_custom_order_id' => $customOrder->custom_order_id,
                'ingredients_ingredient_id' => $ingredient['ingredient_id'],
                'ingredient_percentage' => $ingredient['ingredient_percentage'],
            ]);
        }


        return response()->json(['message' => 'สร้างคำสั่งซื้อ custom order สำเร็จ', 'order' => $customOrder]);
    }

    public function getShippedCustomOrdersForPosition4()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Check if the user's position is 4
        $position = $user->roles()->first()->position_position_id ?? null;
        if ($position !== 4) {
            return response()->json(['error' => 'You do not have permission to view these orders'], 403);
        }

        // Retrieve custom orders with 'shipped' status for the authenticated user
        $customOrders = custom_orders::with('shop')
            ->where('users_user_id', $user->user_id)
            ->where('status', 'shipped')
            ->get();

        return response()->json($customOrders);
    }

}
