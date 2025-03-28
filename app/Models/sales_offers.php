<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sales_offers extends Model
{
    use HasFactory;
    protected $table = 'sales_offers';

    // กำหนด primary key ให้ถูกต้อง
    protected $primaryKey = 'sales_offers_id';

    // หาก primary key ไม่ใช่ integer หรือไม่ใช่ auto-increment ให้ตั้งค่าเพิ่มเติม เช่น:
    // public $incrementing = true;
    // protected $keyType = 'int';

    // กำหนด fillable fields ตามที่ต้องการ
    protected $fillable = [
        'quantity',
        'price_per_unit',
        'status',
        'sales_post_post_id',
        'shops_shop_id',
        'farms_farm_id',
        'users_user_id'
    ];
    public function salePost()
    {
        return $this->belongsTo(sales_post::class, 'sales_post_post_id', 'post_id');
    }
    public function ingredientOrders()
    {
        return $this->hasMany(ingredient_orders::class, 'sales_offers_sales_offers_id', 'sales_offers_id');
    }
    public function payments()
    {
        return $this->morphMany(Payments::class, 'paymentable');
    }

    public function shop()
    {
        return $this->belongsTo(shops::class, 'shops_shop_id', 'shop_id');
    }

}
