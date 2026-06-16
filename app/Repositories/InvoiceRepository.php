<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Interfaces\Repositories\InvoiceRepositoryInterface;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }
}
