<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\farms;
use Illuminate\Support\Facades\Auth;

class FarmsController extends Controller
{
    // ฟังก์ชันแสดงข้อมูลฟาร์มของผู้ใช้งาน
    public function showFarm()
    {
        $user = Auth::user();
        $farm = farms::where('users_user_id', $user->user_id)->first();

        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $farm
        ]);
    }
    public function updateFarm(Request $request)
    {
        $user = auth::user();
        $farm = farms::where('users_user_id', $user->user_id)->first();

        if (!$farm) {
            return response()->json(['message' => 'Farm not found'], 404);
        }

        // ตรวจสอบข้อมูลที่รับมา
        $request->validate([
            'farm_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_account' => 'required|string|max:255',
            'bank_number' => 'required|string|max:255',
        ]);

        // อัปเดตข้อมูลฟาร์ม
        $farm->update([
            'farm_name' => $request->farm_name,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'bank_number' => $request->bank_number,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Farm updated successfully',
            'data' => $farm
        ]);
    }
}
