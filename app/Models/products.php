<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class products extends Model
{
    use HasFactory;
    protected $table = 'products';         // ระบุชื่อตาราง (หากไม่ตรงกับชื่อ model)
    protected $primaryKey = 'product_id';  // ตั้งค่าให้ใช้ 'product_id' เป็น PK
    public $incrementing = true;           // หาก product_id เป็น auto_increment
    protected $keyType = 'int';            // หากเป็น integer

    protected $fillable = [
        'name',
        'description',
        'price',
        'volume',
        'stock_quantity',
        'image_url',
        'gender_target',
        'fragrance_strength',
        'status',
        'shops_shop_id',  // FK ของ Shop
        // created_at จะถูก set อัตโนมัติหากใช้ timestamp ของ Laravel
    ];
    public function shop()
    {
        return $this->belongsTo(\App\Models\shops::class, 'shops_shop_id', 'shop_id');
    }
    // กำหนดความสัมพันธ์แบบ many-to-many กับ FragranceTone
    public function fragranceTones()
    {
        return $this->belongsToMany(
            \App\Models\fragrance_tone::class,  // Model ที่เกี่ยวข้อง
            'fragrance_tone_filter',             // ชื่อตาราง pivot
            'products_product_id',               // FK สำหรับสินค้าใน pivot table
            'fragrance_tone_fragrance_tone_id'   // FK สำหรับ fragrance_tone ใน pivot table
        );
    }

}
