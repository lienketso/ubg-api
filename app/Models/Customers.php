<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Customers extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'ec_customers';
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'avatar',
        'dob'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
