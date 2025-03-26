<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class shops extends Model
{
    use HasFactory;
    protected $table = 'shops';
    protected $primaryKey = 'shop_id';  // <-- ตั้งค่า PK ให้ตรงกับตาราง
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // ถ้าต้องการ fillable ก็เพิ่มได้
    protected $fillable = [
        'shop_name',
        'shop_image',
        'description',
        'accepts_custom',
        'bank_name',
        'bank_account',
        'bank_number',
        'users_user_id',
        'addresses_address_id',
    ];
    public function address()
    {
        // belongsTo(Address::class, 'ชื่อคอลัมน์ foreign key ใน shops', 'ชื่อคอลัมน์ primary key ใน addresses')
        return $this->belongsTo(addresses::class, 'addresses_address_id', 'address_id');
    }


}
