<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\MitraCategory;
use App\Models\Mitra;

class ProductMitraDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Mitra categories
        $mCats = ['Supplier', 'Reseller', 'Customer', 'Distributor'];
        foreach ($mCats as $name) {
            MitraCategory::updateOrCreate(['name' => $name], ['is_active' => true]);
        }

        // 2. Product Categories
        $pCats = [
            'Kopi' => ['Arabika', 'Robusta', 'House Blend'],
            'Powder' => ['Chocolate', 'Matcha', 'Taro'],
            'Syrup' => ['Vanilla', 'Caramel', 'Hazelnut'],
            'Equipment' => ['Espresso Machine', 'Grinder', 'V60 Set']
        ];

        foreach ($pCats as $catName => $subs) {
            $cat = ProductCategory::updateOrCreate(['name' => $catName], ['is_active' => true]);
            foreach ($subs as $subName) {
                ProductSubCategory::updateOrCreate(
                    ['product_category_id' => $cat->id, 'name' => $subName],
                    ['is_active' => true]
                );
            }
        }
    }
}
