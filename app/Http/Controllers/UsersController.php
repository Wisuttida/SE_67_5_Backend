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
        $users = User::all();
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

    // เพิ่มข้อมูลผู้ใช้ใหม่
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

    // แก้ไขข้อมูลโปรไฟล์ผู้ใช้
    public function update(Request $request)
    {
        // ดึงข้อมูลผู้ใช้ที่ล็อกอินอยู่
        $user = Auth::user();

        // ตรวจสอบข้อมูลที่รับมา
        $validated = $request->validate([
            'username' => 'sometimes|required|unique:users,username,' . $user->user_id . ',user_id',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->user_id . ',user_id',
            'password' => 'sometimes|required|min:6',
            'phone_number' => 'sometimes|required',
            'first_name' => 'sometimes|required',
            'last_name' => 'sometimes|required'
        ]);

        // หากมีการอัปเดตรหัสผ่าน ให้แปลงรหัสผ่านใหม่
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // อัปเดตข้อมูลผู้ใช้
        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User profile updated successfully',
            'data' => $user
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
