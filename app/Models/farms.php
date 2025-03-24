<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class farms extends Model
{
    use HasFactory;
    protected $table = 'farms';
    protected $primaryKey = 'farm_id';  // <-- ตั้งค่า PK ให้ตรงกับตาราง
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // ถ้าต้องการ fillable ก็เพิ่มได้
    protected $fillable = [
        'farm_name',
        'farm_image',
        'description',
        'bank_name',
        'bank_account',
        'bank_number',
        'users_user_id',
        'addresses_address_id',
    ];
    // ในโมเดล Farms
    public function user()
    {
        return $this->belongsTo(User::class, 'users_user_id'); // ตรวจสอบให้ใช้ 'users_user_id' ไม่ใช่ 'user_user_id'
    }

}
