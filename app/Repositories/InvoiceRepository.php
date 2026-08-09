<?php

namespace App\Repositories;

use App\Interfaces\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        $this->model = $model;
    }

    public function allForIndex()
    {
        return Invoice::with('salesOrder.mitra')->latest('invoice_date')->get();
    }
}
