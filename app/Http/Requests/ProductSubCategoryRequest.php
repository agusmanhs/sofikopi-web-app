<?php

namespace App\Http\Requests;

class ProductSubCategoryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'product_category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'template_fields' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }
}
