<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapController extends Controller
{
    // ดึงข้อมูลอำเภอจากจังหวัดที่เลือก
    public function getDistricts($province_id)
    {
        // ตัวอย่างการเรียก API (ปรับ URL และพารามิเตอร์ตาม API ที่ใช้จริง)
        $response = Http::get("https://api.example.com/provinces/{$province_id}/districts");

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to fetch districts'
        ], 500);
    }

    // ดึงข้อมูลตำบลและรหัสไปรษณีย์จากอำเภอที่เลือก
    public function getSubdistricts($district_id)
    {
        $response = Http::get("https://api.example.com/districts/{$district_id}/subdistricts");

        if ($response->successful()) {
            // หากมีมากกว่าหนึ่งรหัสไปรษณีย์ อาจจะให้แยกรายการออกมาให้ผู้ใช้เลือก
            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unable to fetch subdistricts'
        ], 500);
    }
}
