<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class stock_transaction extends Model
{
    use HasFactory;
    protected $table = 'stock_transaction';
    public $timestamps = false;
    protected $fillable = [
        'transaction_type',
        'quantity',
        'transaction_date',
        'products_product_id'
    ];
    public function product()
    {
        return $this->belongsTo(\App\Models\products::class, 'products_product_id', 'product_id');
    }
}
