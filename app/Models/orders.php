<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class orders extends Model
{
    use HasFactory;
    protected $table = 'orders'; // ระบุชื่อตารางให้ตรงกับฐานข้อมูล
    protected $primaryKey = 'order_id'; // กำหนด Primary Key
    public $timestamps = true; // ใช้ timestamp

    protected $fillable = [
        'users_user_id',
        'total_amount',
        'status',
        'addresses_address_id',
        'shops_shop_id'
    ];

    public function orderItems()
    {
        return $this->hasMany(order_items::class, 'orders_order_id', 'order_id');
    }

    public function payment()
    {
        return $this->morphOne(payments::class, 'paymentable');
    }

    public function addresses()
    {
        return $this->belongsTo(addresses::class, 'addresses_address_id');
    }

    public function user()
    {
        return $this->belongsTo(users::class, 'users_user_id', 'user_id');
    }


    public function shop()
    {
        return $this->belongsTo(shops::class, 'shops_shop_id');
    }

}
