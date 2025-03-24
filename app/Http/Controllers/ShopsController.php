<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\shops;
use Illuminate\Support\Facades\Auth;

class ShopsController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $shop = shops::where('users_user_id', $user->user_id)->first();

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        // ตรวจสอบข้อมูลที่รับมา
        $request->validate([
            'shop_name' => 'sometimes|required|string|max:255',
            'accepts_custom' => 'sometimes|required|boolean',
        ]);

        // อัปเดตข้อมูลร้านค้า
        $shop->update([
            'shop_name' => $request->shop_name,
            'accepts_custom' => $request->accepts_custom,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => $shop
        ]);
    }
    public function updateBank(Request $request)
    {
        $user = Auth::user();
        $shop = shops::where('users_user_id', $user->user_id)->first();

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        // ตรวจสอบข้อมูลที่รับมา
        $request->validate([
            'bank_name' => 'sometimes|required|string|max:255',
            'bank_account' => 'sometimes|required|string|max:255',
            'bank_number' => 'sometimes|required|string|max:255',
        ]);

        // อัปเดตข้อมูลร้านค้า
        $shop->update([
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
    public function show(Request $request) {
        $user = Auth::user();
        $shop = shops::where('users_user_id', $user->user_id)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => ['shop' => $shop]
        ]);
    }
}
