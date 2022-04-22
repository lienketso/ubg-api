<?php


namespace App\Repositories;


use App\Models\Payments;
use Prettus\Repository\Eloquent\BaseRepository;

class PaymentRepository extends BaseRepository
{
    public function model()
    {
        // TODO: Implement model() method.
        return Payments::class;
    }
}
