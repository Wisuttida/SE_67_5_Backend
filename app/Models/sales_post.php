<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sales_post extends Model
{
    use HasFactory;
    protected $table = 'sales_post';
    protected $primaryKey = 'post_id';
    public $timestamps = false;

    protected $fillable = [
        'description',
        'price_per_unit',
        'amount',
        'unit',
        'farms_farm_id',
        'ingredients_ingredient_id',
        'status',
    ];

    // ความสัมพันธ์กับเกษตรกร (farms)
    public function farm()
    {
        return $this->belongsTo(Farms::class, 'farms_farm_id');
    }

    public function payment()
    {
        return $this->morphOne(payments::class, 'paymentable');
    }
}
