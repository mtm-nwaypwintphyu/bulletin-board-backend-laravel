<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserTypeEnum;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile',
        'type',
        'phone',
        'address',
        'dob',
        'create_user_id',
        'updated_user_id',
        'deleted_user_id',
    ];

    // User type enums
    protected $casts = [
        'type' => UserTypeEnum::class,
    ];

    protected $hidden = [
        'password',
    ];

    // get creator
    public function creator() {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
