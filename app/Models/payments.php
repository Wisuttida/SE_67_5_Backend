<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payments extends Model
{
    use HasFactory;
    protected $table = 'payments'; // ระบุชื่อตารางให้ตรงกับฐานข้อมูล
    protected $primaryKey = 'payment_id'; // กำหนด Primary Key
    public $timestamps = true; // ใช้ timestamp
    protected $fillable = [
        'orders_order_id',
        'amount',
        'payment_proof_url', // หรือเปลี่ยนเป็น 'image_url' ถ้าใช้ field นั้น
        'status'
    ];
    public function order()
    {
        return $this->belongsTo(orders::class, 'orders_order_id', 'order_id');
    }

}
