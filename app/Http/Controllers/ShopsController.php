<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\shops;
use Illuminate\Support\Facades\Auth;

class ShopsController extends Controller
{
    public function updateShop(Request $request)
    {
        $user = Auth::user();
        $shop = shops::where('users_user_id', $user->user_id)->first();

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        // ตรวจสอบข้อมูลที่รับมา
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'accepts_custom' => 'required|boolean',
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|string|max:255',
            'bank_number' => 'required|string|max:255',
        ]);

        // อัปเดตข้อมูลร้านค้า
        $shop->update([
            'shop_name' => $request->shop_name,
            'accepts_custom' => $request->accepts_custom,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'bank_number' => $request->bank_number,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => $shop
        ]);
    }
}
