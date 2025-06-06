<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\buy_post;
use App\Models\farms;

class buy_offers extends Model
{
    use HasFactory;
    protected $primaryKey = 'buy_offers_id';
    protected $fillable = [
        'quantity',
        'price_per_unit',
        'status',  // เพิ่ม status ที่นี่
        'buy_post_post_id',
        'farms_farm_id',
    ];
    public function buyPost()
    {
        return $this->belongsTo(buy_post::class, 'buy_post_post_id', 'post_id');
    }
    public function farm()
    {
        return $this->belongsTo(Farms::class, 'farms_farm_id', 'farm_id');
    }

}
