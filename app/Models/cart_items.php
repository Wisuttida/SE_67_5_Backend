<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cart_items extends Model
{
    use HasFactory;
    protected $table = 'cart_items';
    protected $primaryKey = 'cart_item_id';

    protected $fillable = [
        'cart_cart_id',
        'products_product_id',
        'quantity',
        'price'
    ];

    public function product()
    {
        return $this->belongsTo(products::class, 'products_product_id', 'product_id');
    }

}
