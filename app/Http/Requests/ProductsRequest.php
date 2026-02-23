<?php

namespace App\Http\Requests;

class ProductsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'cover' => $this->isMethod('POST') ? 'required|image|mimes:jpeg,png,jpg,gif|max:2048' : 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'description' => 'required|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}