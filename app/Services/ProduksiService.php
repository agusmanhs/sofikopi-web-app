<?php

namespace App\Services;

use App\Interfaces\Repositories\ProduksiRepositoryInterface;
use App\Models\Products;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProduksiService extends BaseService
{
    public function __construct(ProduksiRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getAllWithRelations()
    {
        return $this->repository->allWithRelations();
    }

    public function storeProduksi(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['user_id'] = auth()->id();
            $jumlah = abs((int) $data['jumlah']);

            // Auto-determine sign based on transaction type
            $tipe = $data['tipe'];
            if (in_array($tipe, ['penjualan'])) {
                $data['jumlah'] = -$jumlah;
            } elseif ($tipe === 'adjustment') {
                // Adjustment allows negative values from user input
                $data['jumlah'] = (int) $data['jumlah'];
            } else {
                // produksi, retur -> always positive
                $data['jumlah'] = $jumlah;
            }

            // Validate stock for deductions
            $product = Products::find($data['product_id']);
            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => 'Produk tidak ditemukan.',
                ]);
            }

            $newStock = $product->current_stock + $data['jumlah'];
            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'jumlah' => "Stok tidak mencukupi. Stok saat ini: {$product->current_stock}. Tidak bisa mengurangi sebanyak " . abs($data['jumlah']) . ".",
                ]);
            }

            $produksi = $this->repository->create($data);

            // Update current_stock
            $product->current_stock = $newStock;
            $product->save();

            return $produksi;
        });
    }

    public function deleteProduksi($id)
    {
        return DB::transaction(function () use ($id) {
            $produksi = $this->repository->find($id);

            // Revert stock
            $product = Products::find($produksi->product_id);
            if ($product) {
                $product->current_stock -= $produksi->jumlah;
                $product->save();
            }

            return $this->repository->delete($id);
        });
    }
}
