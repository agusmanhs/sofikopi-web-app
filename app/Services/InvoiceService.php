<?php

namespace App\Services;

use App\Repositories\InvoiceRepository;

class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepository $repository)
    {
        parent::__construct($repository);
    }

    public function updateInvoiceStatus($id, array $data)
    {
        $invoice = $this->repository->find($id);
        if (!$invoice) {
            throw new \Exception("Invoice tidak ditemukan.");
        }

        $updateData = [
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $invoice->notes,
        ];

        if ($data['status'] === 'lunas') {
            $updateData['paid_at'] = !empty($data['paid_at']) ? $data['paid_at'] : now();
        } else {
            $updateData['paid_at'] = null;
        }

        $invoice->update($updateData);

        return $invoice;
    }
}

