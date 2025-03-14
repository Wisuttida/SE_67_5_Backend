<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class custom_order_details extends Model
{
    use HasFactory;
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'ingredient_percentage',
        'ingredients_ingredient_id',
        'custom_orders_custom_order_id'
    ];

    // ความสัมพันธ์กับ CustomOrder
    public function customOrder()
    {
        return $this->belongsTo(custom_orders::class, 'custom_orders_custom_order_id', 'custom_order_id');
    }
}
