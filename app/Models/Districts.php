<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Districts extends Model
{
    use HasFactory;
    /**
     * @var string
     */
    protected $table = 'districts';

    /**
     * @var string[]
     */
    protected $fillable = [];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function getWards()
    {
        return $this->hasMany(Ward::class, 'district_id', 'id');
    }
}
