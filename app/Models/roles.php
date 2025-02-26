<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class roles extends Model
{
    use HasFactory;
    protected $primaryKey = 'role_id';

    // กำหนดความสัมพันธ์กลับไปที่ User
    public function users()
    {
        return $this->belongsTo(User::class, 'users_user_id', 'user_id');
    }

    // กำหนดความสัมพันธ์กับ Position
    public function positions()
    {
        return $this->belongsTo(Position::class, 'position_position_id', 'position_id');
    }
}
