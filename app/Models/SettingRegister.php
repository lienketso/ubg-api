<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingRegister extends Model
{
    use HasFactory;
    protected $table = 'setting_register';
    protected $fillable = ['title','thumbnail','total_plus_ubgxu','start_date','expire_date','type'];
}
