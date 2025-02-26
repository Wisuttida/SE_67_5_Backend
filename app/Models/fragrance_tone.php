<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fragrance_tone extends Model
{
    use HasFactory;
    protected $table = 'fragrance_tone';

    protected $primaryKey = 'fragrance_tone_id';

    protected $fillable = [
        'fragrance_tone_name'
    ];

    public function products()
    {
        return $this->belongsToMany(
            products::class,
            'fragrance_tone_filter',
            'fragrance_tone_fragrance_tone_id', // FK ใน pivot ที่อ้างถึง fragrance_tone
            'products_product_id'               // FK ใน pivot ที่อ้างถึง products
        );
    }

}
