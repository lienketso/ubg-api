<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;
    /**
     * @var string
     */
    protected $table = 'provinces';

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

    public function getDistricts()
    {
        return $this->hasMany(Districts::class, 'province_id', 'id');
    }
}
