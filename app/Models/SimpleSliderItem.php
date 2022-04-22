<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpleSliderItem extends Model
{
    use HasFactory;
    protected $table = 'simple_slider_items';
    protected $fillable = ['simple_slider_id','title','image','link','order'];
}
