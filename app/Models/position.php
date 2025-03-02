<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class position extends Model
{
    use HasFactory;
    protected $primaryKey = 'position_id';
    protected $table = 'position';
    public $timestamps = false;

    // ถ้าต้องการความสัมพันธ์กลับไปยังผู้ใช้ผ่าน roles
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'roles',
            'position_position_id',
            'users_user_id'
        );
    }
}
