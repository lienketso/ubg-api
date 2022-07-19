<?php


namespace App\Repositories;


use App\Models\Shipment;
use Prettus\Repository\Eloquent\BaseRepository;

class ShipmentRepository extends BaseRepository
{
    public function model()
    {
        return Shipment::class;
    }
}
