<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sales_offers extends Model
{
    use HasFactory;
    public function salePost()
    {
        return $this->belongsTo(sales_post::class, 'sales_post_post_id', 'post_id');
    }
}
