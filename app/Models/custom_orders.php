<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class custom_orders extends Model
{
    use HasFactory;

    protected $table = 'custom_orders'; // ระบุชื่อตารางให้ตรงกับฐานข้อมูล
    protected $primaryKey = 'custom_order_id';

    protected $fillable = [
        'fragrance_name',
        'description',
        'intensity_level',
        'volume_ml',
        'status',
        'shops_shop_id',
        'users_user_id',
        'is_tester',
        'tester_price',
        'custom_order_price',
        'addresses_address_id', // ต้องเพิ่มบรรทัดนี้
    ];

    // ความสัมพันธ์กับ custom_order_details (แบบ one-to-one)
    public function detail()
    {
        return $this->hasOne(custom_order_details::class, 'custom_orders_custom_order_id', 'custom_order_id');
    }

    // ความสัมพันธ์กับร้านค้า (Shop)
    public function shop()
    {
        return $this->belongsTo(shops::class, 'shops_shop_id', 'shop_id');
    }

    // ความสัมพันธ์กับลูกค้า (User)
    public function user()
    {
        return $this->belongsTo(User::class, 'users_user_id', 'user_id');
    }

    public function payment()
    {
        return $this->morphOne(payments::class, 'paymentable');
    }
}
