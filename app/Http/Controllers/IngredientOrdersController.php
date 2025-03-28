<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ingredient_orders;
use Illuminate\Support\Facades\Auth;


class IngredientOrdersController extends Controller
{
    public function showOrderStatusForShop()
    {
        $user = Auth::user();
        $shop = $user->shop; // ค้นหาร้านของผู้ใช้

        if (!$shop) {
            return response()->json(['error' => 'ไม่พบร้านของคุณ'], 404);
        }

        // ดึงรายการคำสั่งซื้อที่เกี่ยวข้องกับร้าน
        $orders = ingredient_orders::where('shops_shop_id', $shop->shop_id)
            ->with('farm', 'address') // ร่วมข้อมูลฟาร์มและที่อยู่
            ->get();

        return response()->json(['orders' => $orders]);
    }

    // แสดงสถานะคำสั่งซื้อสำหรับฟาร์ม
    public function showOrderStatusForFarm()
    {
        $user = Auth::user();
        $farm = $user->farm; // ค้นหาฟาร์มของผู้ใช้

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ดึงรายการคำสั่งซื้อที่เกี่ยวข้องกับฟาร์ม
        $orders = ingredient_orders::where('farms_farm_id', $farm->farm_id)
            ->with('shop', 'address') // ร่วมข้อมูลร้านและที่อยู่
            ->get();

        return response()->json(['orders' => $orders]);
    }

    // แสดงรายละเอียดคำสั่งซื้อ
    public function showOrderDetails($orderId)
    {
        $order = ingredient_orders::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // ดึงข้อมูลรายละเอียดคำสั่งซื้อพร้อมข้อมูลที่เกี่ยวข้อง
        $order->load('farm', 'shop', 'address');

        return response()->json(['order' => $order]);
    }
    // แสดงคำสั่งซื้อที่กรองตามสถานะสำหรับร้าน
    public function showOrdersByStatusForShop(Request $request)
    {
        $user = Auth::user();
        $shop = $user->shop; // ค้นหาร้านของผู้ใช้

        if (!$shop) {
            return response()->json(['error' => 'ไม่พบร้านของคุณ'], 404);
        }

        // ตรวจสอบว่ามีการส่งสถานะมาหรือไม่
        $status = $request->query('status'); // รับค่าจาก query parameter 'status'

        // กรองคำสั่งซื้อจากสถานะที่ระบุ
        $orders = ingredient_orders::where('shops_shop_id', $shop->shop_id)
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->get();

        return response()->json(['orders' => $orders]);
    }

    // แสดงคำสั่งซื้อที่กรองตามสถานะสำหรับฟาร์ม
    public function showOrdersByStatusForFarm(Request $request)
    {
        $user = Auth::user();
        $farm = $user->farm; // ค้นหาฟาร์มของผู้ใช้

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ตรวจสอบค่าของ status ที่ส่งมา
        $status = $request->query('status'); // รับค่าจาก query parameter 'status'

        // ตรวจสอบว่ามีการส่งสถานะมาหรือไม่ และสถานะตรงกับค่าที่ถูกต้อง
        if ($status && !in_array($status, ['pending', 'confirmed', 'delivered', 'shipped'])) {
            return response()->json(['error' => 'สถานะที่ระบุไม่ถูกต้อง'], 400);
        }

        // กรองคำสั่งซื้อจากสถานะที่ระบุและโหลดข้อมูลที่เกี่ยวข้อง (sales_offer, buy_offer, ingredients)
        $orders = ingredient_orders::where('farms_farm_id', $farm->farm_id)
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with([
                'salesOffer' => function ($query) {
                    $query->with('salePost.ingredients'); // โหลดข้อมูลของ ingredients ที่เกี่ยวข้องกับ sales_post
                },
                'buyOffer' => function ($query) {
                    $query->with('buyPost.ingredients'); // โหลดข้อมูลของ ingredients ที่เกี่ยวข้องกับ buy_post
                }
            ])
            ->get();

        // ตรวจสอบผลลัพธ์
        if ($orders->isEmpty()) {
            return response()->json([
                'error' => 'ไม่พบคำสั่งซื้อที่ตรงกับสถานะที่เลือก',
                'farm_id' => $farm->farm_id // แสดง farm_id ทุกกรณีที่ไม่พบคำสั่งซื้อ
            ], 404);
        }

        return response()->json(['orders' => $orders]);
    }



    // แก้ไขสถานะคำสั่งซื้อสำหรับฟาร์ม
    public function updateOrderStatusForFarm(Request $request, $orderId)
    {
        $user = Auth::user();
        $farm = $user->farm; // ค้นหาฟาร์มของผู้ใช้

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ค้นหาคำสั่งซื้อ
        $order = ingredient_orders::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // ตรวจสอบว่า คำสั่งซื้อนี้เป็นของฟาร์มนี้
        if ($order->farms_farm_id != $farm->farm_id) {
            return response()->json(['error' => 'คุณไม่สามารถแก้ไขคำสั่งซื้อนี้ได้'], 403);
        }

        // ตรวจสอบและอัปเดตสถานะ
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled'
        ]);

        $order->status = $request->status;
        $order->save();

        return response()->json(['message' => 'อัปเดตสถานะคำสั่งซื้อสำเร็จ', 'order' => $order]);
    }

    public function updateOrderStatusToShipped($orderId)
    {
        $user = Auth::user();
        $farm = $user->farm; // ค้นหาฟาร์มของผู้ใช้

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ค้นหาคำสั่งซื้อ
        $order = ingredient_orders::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'ไม่พบคำสั่งซื้อ'], 404);
        }

        // ตรวจสอบว่า คำสั่งซื้อนี้เป็นของฟาร์มนี้
        if ($order->farms_farm_id != $farm->farm_id) {
            return response()->json(['error' => 'คุณไม่สามารถแก้ไขคำสั่งซื้อนี้ได้'], 403);
        }

        // ตรวจสอบสถานะของคำสั่งซื้อว่ามีสถานะเป็น 'confirmed' หรือไม่
        if ($order->status !== 'confirmed') {
            return response()->json(['error' => 'คำสั่งซื้อนี้ไม่สามารถอัปเดตเป็น "shipped" ได้ เนื่องจากสถานะไม่ใช่ "confirmed"'], 400);
        }

        // อัปเดตสถานะเป็น "shipped"
        $order->status = 'shipped';
        $order->save();

        return response()->json(['message' => 'อัปเดตสถานะคำสั่งซื้อเป็น "shipped" สำเร็จ', 'order' => $order]);
    }
    public function showPendingOrdersForFarm()
    {
        $user = Auth::user();
        $farm = $user->farm; // ค้นหาฟาร์มของผู้ใช้

        if (!$farm) {
            return response()->json(['error' => 'ไม่พบฟาร์มของคุณ'], 404);
        }

        // ดึงรายการคำสั่งซื้อที่มีสถานะ pending และ sales_offer เป็น confirmed
        $orders = ingredient_orders::where('farms_farm_id', $farm->farm_id)
            ->where('status', 'pending') // คำสั่งซื้อที่สถานะเป็น 'pending'
            ->with([
                'salesOffer' => function ($query) {
                    $query->where('status', 'confirmed') // คำเสนอการขายที่สถานะเป็น 'confirmed'
                        ->with('salePost.ingredients'); // รวมข้อมูลของ ingredients ที่เกี่ยวข้อง
                },
                'address' // รวมข้อมูลที่อยู่ของผู้ซื้อ
            ])
            ->get();

        // ตรวจสอบผลลัพธ์
        if ($orders->isEmpty()) {
            return response()->json([
                'error' => 'ไม่พบคำสั่งซื้อที่ตรงกับเงื่อนไข',
                'farm_id' => $farm->farm_id // แสดง farm_id ทุกกรณีที่ไม่พบคำสั่งซื้อ
            ], 404);
        }

        return response()->json(['orders' => $orders]);
    }

}
