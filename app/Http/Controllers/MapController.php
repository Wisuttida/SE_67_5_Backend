<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapController extends Controller
{
    // ดึงรายชื่อจังหวัดจาก ThaiAddressAPI
    public function getProvinces()
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'your-rapidapi-host.example.com', // เปลี่ยนเป็น host ที่ได้จาก RapidAPI
            'x-rapidapi-key' => 'YOUR_RAPIDAPI_KEY',              // ใส่คีย์ API ที่ได้รับ
        ])->get('https://your-rapidapi-host.example.com/provinces');

        if ($response->successful()) {
            return response()->json([
                'status' => 'success',
                'data' => $response->json()
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Unable to fetch provinces'
        ], 500);
    }

    // ดึงรายชื่ออำเภอตามจังหวัดที่เลือก
    public function getDistricts($province_id)
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'your-rapidapi-host.example.com',
            'x-rapidapi-key' => 'YOUR_RAPIDAPI_KEY',
        ])->get("https://your-rapidapi-host.example.com/provinces/{$province_id}/districts");

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

    // ดึงรายชื่อตำบลและรหัสไปรษณีย์ตามอำเภอที่เลือก
    public function getSubdistricts($district_id)
    {
        $response = Http::withHeaders([
            'x-rapidapi-host' => 'your-rapidapi-host.example.com',
            'x-rapidapi-key' => 'YOUR_RAPIDAPI_KEY',
        ])->get("https://your-rapidapi-host.example.com/districts/{$district_id}/subdistricts");

        if ($response->successful()) {
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
