<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
class UsersController extends Controller
{
    public function index()
    {
        // ดึงข้อมูลผู้ใช้พร้อมข้อมูล shop, farm และ position
        $users = User::with(['shop', 'farm', 'positions'])->get();  // positions จะถูกดึงมาจากความสัมพันธ์ในโมเดล User

        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }


    // แสดงข้อมูลผู้ใช้รายตัว
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone_number' => 'required',
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }
    // เพิ่มข้อมูลผู้ใช้ใหม่โดยผู้ดูแลระบบ
    public function add(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone_number' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'position' => 'required|in:1,4'  // position 1 is admin, 4 is customer
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Create the user with the given position
        $user = User::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }


    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $userData = User::where('user_id', $user->user_id)->first();

        if (!$userData) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // ตรวจสอบข้อมูลที่รับมา
        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'profile_image' => 'sometimes|required|string',
        ]);

        $userData->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'profile_image' => $request->profile_image,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Shop updated successfully',
            'data' => $userData
        ]);
    }
    // แก้ไขข้อมูลโปรไฟล์ผู้ใช้
    // public function update(Request $request)
    // {
    //     // ดึงข้อมูลผู้ใช้ที่ล็อกอินอยู่
    //     $user = Auth::user();

    //     // ตรวจสอบข้อมูลที่รับมา
    //     $validated = $request->validate([
    //         'username' => 'sometimes|required|unique:users,username,' . $user->user_id . ',user_id',
    //         'email' => 'sometimes|required|email|unique:users,email,' . $user->user_id . ',user_id',
    //         'password' => 'sometimes|required|min:6',
    //         'phone_number' => 'sometimes|required',
    //         'first_name' => 'sometimes|required',
    //         'last_name' => 'sometimes|required'
    //     ]);

    //     // หากมีการอัปเดตรหัสผ่าน ให้แปลงรหัสผ่านใหม่
    //     if (isset($validated['password'])) {
    //         $validated['password'] = Hash::make($validated['password']);
    //     }

    //     // อัปเดตข้อมูลผู้ใช้
    //     $user->update($validated);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'User profile updated successfully',
    //         'data' => $user
    //     ], 200);
    // }

    // In UsersController.php
    public function updateActivation(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Check if user has shop and farm
        if ($user->shop) {
            $user->shop->is_activate = $request->input('shop_is_activate');
            $user->shop->save();
        }

        if ($user->farm) {
            $user->farm->is_activate = $request->input('farm_is_activate');
            $user->farm->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Activation status updated successfully',
        ], 200);
    }


    // ลบผู้ใช้
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ], 200);
    }
}
