<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuNode extends Model
{
    use HasFactory;
    protected $table = 'menu_nodes';
    protected $fillable = ['menu_id','parent_id','reference_id','url','title','position','has_child'];

    public function childs() {
        return $this->hasMany(MenuNode::class,'parent_id','id')->orderBy('position','asc');
    }

}
