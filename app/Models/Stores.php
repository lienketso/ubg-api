<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stores extends Model
{
    use HasFactory;
    protected $table = 'mp_stores';
    protected $fillable = ['name','email','phone','address','country','state','city','customer_id','logo','description','content','status'];

    public function products(): HasMany {
        return $this->hasMany(Product::class,'store_id','id');
    }

}
