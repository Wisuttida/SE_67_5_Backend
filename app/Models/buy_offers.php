<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class buy_offers extends Model
{
    use HasFactory;
    public function buyPost()
    {
        return $this->belongsTo(buy_post::class, 'buy_post_post_id', 'post_id');
    }
}
