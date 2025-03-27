<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    // กำหนดให้เป็นค่า incrementing และประเภทเป็น int (ถ้าเป็นไปตามฐานข้อมูล)
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone_number',
        'profile_image'
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function roles()
    {
        return $this->hasMany(roles::class, 'users_user_id', 'user_id');
    }
    public function hasRole($roleName)
    {
        // สมมติว่า roles แต่ละอันมีความสัมพันธ์กับตำแหน่ง (position) ที่สามารถเข้าถึงชื่อ role ได้
        return $this->roles->contains(function ($role) use ($roleName) {
            return $role->position->position_name === $roleName;
        });
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

    public function addresses()
    {
        return $this->hasMany(addresses::class, 'users_user_id', 'user_id');
    }
    // ในโมเดล User
    public function farm()
    {
        return $this->hasOne(Farms::class, 'users_user_id');  // กำหนดความสัมพันธ์กับคอลัมน์ users_user_id
    }

}
