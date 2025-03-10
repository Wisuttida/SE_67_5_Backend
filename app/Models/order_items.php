<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class order_items extends Model
{
    use HasFactory;
    protected $table = 'order_items';
    protected $primaryKey = 'order_item_id';

    protected $fillable = [
        'orders_order_id',
        'products_product_id',
        'quantity',
        'price'
    ];
    public function order()
    {
        return $this->belongsTo(Orders::class, 'orders_order_id', 'order_id');
    }

    // ✅ เพิ่มความสัมพันธ์กับ Products
    public function product()
    {
        return $this->belongsTo(Products::class, 'products_product_id', 'product_id');
    }
}
