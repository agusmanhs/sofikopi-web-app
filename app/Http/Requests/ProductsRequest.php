<?php

namespace App\Http\Requests;

class ProductsRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('product');
        return [
            'product_sub_category_id' => 'required|exists:product_sub_categories,id',
            'sku' => 'required|string|unique:products,sku,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'netto' => 'nullable|numeric|min:0',
            'gross_weight' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|array',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'nullable|boolean',
        ];
    }
}