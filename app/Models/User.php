<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $primaryKey = 'user_id';
    // กำหนดให้เป็นค่า incrementing และประเภทเป็น int (ถ้าเป็นไปตามฐานข้อมูล)
    public $incrementing = true;
    protected $keyType = 'int';

    public function roles()
    {
        return $this->hasMany(roles::class, 'users_user_id', 'user_id');
    }

    // กำหนดความสัมพันธ์แบบ many-to-many กับตำแหน่งผ่านตาราง roles
    public function positions()
    {
        return $this->belongsToMany(
            position::class,
            'roles', // pivot table
            'users_user_id', // FK ใน roles สำหรับผู้ใช้
            'position_position_id' // FK ใน roles สำหรับตำแหน่ง
        );
    }

    public function shop()
    {
        return $this->hasOne(shops::class, 'users_user_id', 'user_id');
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
