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
        'amount',
        'payment_proof_url',
        'status',
        // ไม่ต้องมี orders_order_id อีกแล้ว
        'paymentable_id',
        'paymentable_type',
    ];

    // กำหนด polymorphic relationship
    public function paymentable()
    {
        return $this->morphTo();
    }
}
