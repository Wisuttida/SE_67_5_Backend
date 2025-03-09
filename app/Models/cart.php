<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cart extends Model
{
    use HasFactory;

    protected $table = 'cart'; // ระบุชื่อตารางให้ตรงกับฐานข้อมูล
    protected $primaryKey = 'cart_id'; // กำหนด Primary Key ให้ตรงกับฐานข้อมูล
    protected $fillable = ['users_user_id', 'created_at', 'updated_at'];


    public function user()
    {
        return $this->belongsTo(User::class, 'users_user_id');
    }

    public function cartItems()
    {
        return $this->hasMany(cart_items::class, 'cart_cart_id');
    }
}
