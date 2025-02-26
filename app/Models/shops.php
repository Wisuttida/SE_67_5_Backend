<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class shops extends Model
{
    use HasFactory;
    protected $primaryKey = 'shop_id';  // <-- ตั้งค่า PK ให้ตรงกับตาราง
    public $incrementing = true;
    protected $keyType = 'int';

    // ถ้าต้องการ fillable ก็เพิ่มได้
    protected $fillable = [
        'shop_name',
        'description',
        'accepts_custom',
        'bank_name',
        'bank_account',
        'bank_number',
        'users_user_id',
        'addresses_address_id',
    ];
}
