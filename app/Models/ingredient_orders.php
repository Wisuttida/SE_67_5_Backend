<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ingredient_orders extends Model
{
    use HasFactory;
    // ระบุชื่อ table ถ้าชื่อ table ไม่ตรงกับ convention (แต่ในที่นี้ table ชื่อ ingredient_orders ก็ตรงกับ convention)
    protected $table = 'ingredient_orders';

    // เนื่องจาก primary key ในตารางของคุณไม่ใช่ "id" ให้ระบุชื่อ primary key ใหม่
    protected $primaryKey = 'ingredient_orders_id';

    // กำหนดว่าฟิลด์ไหนที่อนุญาตให้ mass assignment ได้
    protected $fillable = [
        'total',
        'status',
        'farms_farm_id',
        'shops_shop_id',
        'addresses_address_id',
        'sales_offers_sales_offers_id',
        'buy_offers_buy_offers_id',
    ];

    // หากตารางของคุณมีฟิลด์ created_at และ updated_at (timestamps) ให้เปิดใช้งาน (ค่าเริ่มต้นเป็น true)
    public $timestamps = true;

    // ตัวอย่างการกำหนดความสัมพันธ์กับ Model อื่น (ถ้าจำเป็น)
    public function farm()
    {
        return $this->belongsTo(farms::class, 'farms_farm_id', 'farm_id');
    }


    public function shop()
    {
        return $this->belongsTo(shops::class, 'shops_shop_id');
    }

    public function address()
    {
        return $this->belongsTo(addresses::class, 'addresses_address_id');
    }

    // หากต้องการความสัมพันธ์กับ offers สามารถกำหนดเพิ่มเติมได้
    public function salesOffer()
    {
        return $this->belongsTo(sales_offers::class, 'sales_offers_sales_offers_id');
    }

    public function buyOffer()
    {
        return $this->belongsTo(buy_offers::class, 'buy_offers_buy_offers_id');
    }
    public function payment()
    {
        return $this->morphOne(payments::class, 'paymentable');
    }

    public function ingredientOrders()
    {
        return $this->hasMany(ingredient_orders::class, 'sales_offers_sales_offers_id');
    }


}
