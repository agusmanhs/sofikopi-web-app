<?php

namespace App\Imports;

use App\Models\Products;
use App\Models\ProductSubCategory;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if name is empty
        if (empty($row['nama_produk'])) {
            return null;
        }

        // 1. Handle Kategori & Sub Kategori
        $categoryName = $this->formatString($row['kategori'] ?? 'Umum');
        $category = ProductCategory::firstOrCreate(
            ['name' => $categoryName],
            ['is_active' => true]
        );

        $subCategoryName = $this->formatString($row['sub_kategori'] ?? 'Lain-lain');
        $subCategory = ProductSubCategory::firstOrCreate(
            [
                'product_category_id' => $category->id,
                'name' => $subCategoryName
            ],
            ['is_active' => true]
        );

        // 2. Handle SKU (Gunakan yang ada atau generate)
        $sku = !empty($row['sku']) ? $row['sku'] : $this->generateSKU();

        return new Products([
            'product_sub_category_id' => $subCategory->id,
            'sku'                     => $sku,
            'name'                    => $this->formatString($row['nama_produk']),
            'description'             => $row['deskripsi'] ?? '',
            'buying_price'            => (int) ($row['harga_beli'] ?? 0),
            'selling_price'           => (int) ($row['harga_jual'] ?? 0),
            'current_stock'           => 0, // Sesuai Request USER: Default 0
            'min_stock'               => (int) ($row['stok_minimal'] ?? 0),
            'unit'                    => $row['satuan'] ?? 'Pcs',
            'is_active'               => true,
        ]);
    }

    /**
     * Helper to format string to Capitalize (Title Case)
     */
    private function formatString(?string $string): string
    {
        if (empty($string)) return '';
        return Str::title(strtolower(trim($string)));
    }

    /**
     * Generate unique SKU
     */
    private function generateSKU(): string
    {
        $prefix = 'PROD-' . date('Ymd');
        $count = Products::where('sku', 'like', $prefix . '%')->count();
        return $prefix . Str::padLeft($count + 1, 3, '0');
    }
}
