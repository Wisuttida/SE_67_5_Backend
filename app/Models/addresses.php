<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class addresses extends Model
{
    use HasFactory;
    protected $table = 'addresses';

    // กำหนด primary key ให้ตรงกับฐานข้อมูล
    protected $primaryKey = 'address_id';

    // ถ้าเป็นค่า auto-increment
    public $incrementing = true;

    // ถ้า primary key เป็นชนิด int
    protected $keyType = 'int';

    // กำหนด mass assignable fields (ปรับตามที่คุณต้องการ)
    protected $fillable = [
        'fname',               // เปลี่ยนจาก receiver_name เป็น fname
        'lname',               // เปลี่ยนจาก receiver_name เป็น lname
        'phonenumber',
        'street_name',
        'building',
        'house_number',
        'is_default',
        'users_user_id',
        'province',
        'district',
        'subDistrict',
        'zipcode',
        'position_id', // เพิ่ม field นี้
    ];
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'position_id');
    }

}
