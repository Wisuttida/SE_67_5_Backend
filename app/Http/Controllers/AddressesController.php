<?php

namespace App\Http\Controllers;
use App\Models\addresses;
use Illuminate\Http\Request;

class AddressesController extends Controller
{
    // ดึงข้อมูลที่อยู่ทั้งหมดของผู้ใช้ (อาจจะใช้การกรองด้วยผู้ใช้ที่ล็อกอินอยู่)
    public function index(Request $request)
    {
        // รับตำแหน่งปัจจุบันของผู้ใช้ผ่านความสัมพันธ์ใน roles
        $positionId = $request->user()->roles()->value('position_position_id');

        // ดึงที่อยู่ที่ตรงกับผู้ใช้และตำแหน่งที่ตรวจสอบได้
        $addresses = addresses::where('users_user_id', $request->user()->user_id)
            // ->where('position_id', $positionId)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ], 200);
    }
    public function matchId($id)
    {
        $addresses = addresses::where('address_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ], 200);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => 'sometimes|required',
            'lname' => 'sometimes|required',
            'phonenumber' => 'sometimes|required',
            'street_name' => 'sometimes|nullable',
            'building' => 'sometimes|nullable',
            'house_number' => 'sometimes|required',
            'province' => 'sometimes|required',
            'district' => 'sometimes|required',
            'subDistrict' => 'sometimes|required',
            'zipcode' => 'sometimes|required',
            'position_id' => 'sometimes|required',
            'is_default' => 'sometimes|boolean',
        ]);

        // หาก is_default เป็น true ให้เปลี่ยนค่า is_default ของที่อยู่อื่น ๆ ของผู้ใช้เป็น 0
        if (isset($validated['is_default']) && $validated['is_default']) {
            addresses::where('users_user_id', $request->user()->user_id)->where('position_id', $request->position_id)
                ->update(['is_default' => 0]);
        }

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

        // ตรวจสอบสิทธิ์ของผู้ใช้
        if ($address->users_user_id !== $request->user()->user_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'fname' => 'sometimes|required',
            'lname' => 'sometimes|required',
            'phonenumber' => 'sometimes|required',
            'street_name' => 'sometimes|nullable',
            'building' => 'sometimes|nullable',
            'house_number' => 'sometimes|required',
            'province' => 'sometimes|required',
            'district' => 'sometimes|required',
            'subDistrict' => 'sometimes|required',
            'zipcode' => 'sometimes|required',
            'position_id' => 'sometimes|required',
            'is_default' => 'sometimes|boolean',
        ]);

        // หากอัปเดตเป็น default ให้เปลี่ยนค่า is_default ของที่อยู่อื่น ๆ ของผู้ใช้เป็น 0
        if (isset($validated['is_default']) && $validated['is_default']) {
            addresses::where('users_user_id', $request->user()->user_id)->where('position_id', $request->position_id)
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

        $positionId = $request->user()->roles()->value('position_position_id');
        if ($address->position_id !== $positionId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Position mismatch'
            ], 403);
        }

        $address->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Address deleted successfully'
        ], 200);
    }

}
