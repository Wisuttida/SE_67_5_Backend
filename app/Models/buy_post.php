<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class buy_post extends Model
{
    use HasFactory;
    protected $table = 'buy_post';
    protected $primaryKey = 'post_id';
    public $timestamps = true; // หรือปรับให้เหมาะสมตามความต้องการ

    protected $fillable = [
        'description',
        'price_per_unit',
        'amount',
        'unit',
        'shops_shop_id',
        'ingredients_ingredient_id',
        'status',
    ];

    // สมมุติว่าร้านมีความสัมพันธ์กับโพสต์รับซื้อ
    public function shop()
    {
        return $this->belongsTo(Shops::class, 'shops_shop_id', 'shop_id');
    }
    // กำหนดความสัมพันธ์แบบ polymorphic กับ payments
    public function payment()
    {
        return $this->morphOne(payments::class, 'paymentable');
    }

    // เพิ่มฟังก์ชันในโมเดล buy_post
    public function ingredients()
    {
        return $this->belongsTo(Ingredients::class, 'ingredients_ingredient_id', 'ingredient_id');
    }


}
