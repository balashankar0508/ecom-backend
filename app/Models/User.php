<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    
    use HasApiTokens, Notifiable;
    protected $fillable = ['name', 'email', 'phone', 'password', 'role'];

    protected $hidden = ['password'];

    protected $casts = ['role' => 'string'];
    
    

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
}