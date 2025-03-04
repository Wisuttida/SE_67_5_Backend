<?php

namespace App\Http\Controllers;
use App\Models\addresses;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    // ดึงข้อมูลที่อยู่ทั้งหมดของผู้ใช้ (อาจจะใช้การกรองด้วยผู้ใช้ที่ล็อกอินอยู่)
    public function index(Request $request)
    {
        $addresses = addresses::where('users_user_id', $request->user()->user_id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ], 200);
    }

    // เพิ่มที่อยู่ใหม่
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_name' => 'required',
            'phonenumber' => 'required',
            'street_name' => 'nullable',
            'building' => 'nullable',
            'house_number' => 'required',

            // สามารถกำหนด users_user_id ผ่าน auth หรือจาก request ถ้าเป็นกรณี admin
            'province' => 'sometimes|required',
            'amphoe' => 'sometimes|required',
            'tambon' => 'sometimes|required',
            'zipcode' => 'sometimes|required',
            'is_default' => 'boolean',
        ]);

        // หาก is_default เป็น true ให้เปลี่ยนค่า is_default ของที่อยู่อื่น ๆ ของผู้ใช้เป็น 0
        if (isset($validated['is_default']) && $validated['is_default']) {
            addresses::where('users_user_id', $request->user()->user_id)
                ->update(['is_default' => 0]);
        }

        // กำหนดผู้ใช้จากข้อมูลการล็อกอิน (หรือรับค่าจาก request ถ้าเป็น admin)
        $validated['users_user_id'] = $request->user()->user_id;

        $address = addresses::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Address added successfully',
            'data' => $address
        ], 201);
    }

    // แก้ไขที่อยู่
    public function update(Request $request, $id)
    {
        $address = addresses::findOrFail($id);

        // ตรวจสอบสิทธิ์: ให้แน่ใจว่าที่อยู่นี้เป็นของผู้ใช้ที่ล็อกอินอยู่
        if ($address->users_user_id !== $request->user()->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'receiver_name' => 'sometimes|required',
            'phonenumber' => 'sometimes|required',
            'street_name' => 'nullable',
            'building' => 'nullable',
            'house_number' => 'sometimes|required',
            // สามารถกำหนด users_user_id ผ่าน auth หรือจาก request ถ้าเป็นกรณี admin
            'province' => 'sometimes|required',
            'amphoe' => 'sometimes|required',
            'tambon' => 'sometimes|required',
            'zipcode' => 'sometimes|required',
            'is_default' => 'boolean',
        ]);

        // หากอัปเดตเป็น default ให้เปลี่ยนค่า is_default ของที่อยู่อื่น ๆ ของผู้ใช้เป็น 0
        if (isset($validated['is_default']) && $validated['is_default']) {
            addresses::where('users_user_id', $request->user()->user_id)
                ->update(['is_default' => 0]);
        }

        $address->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Address updated successfully',
            'data' => $address
        ], 200);
    }

    // ลบที่อยู่
    public function destroy(Request $request, $id)
    {
        $address = addresses::findOrFail($id);

        if ($address->users_user_id !== $request->user()->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully'
        ], 200);
    }
}
