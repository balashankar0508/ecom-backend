<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->role === 'admin';
    }

    public function rules()
    {
        $id = $this->product?->id ?? null;
        return [
            'title' => 'required|string|max:200',
            'slug' => 'required|string|max:191|unique:products,slug,' . ($id ?? 'NULL') . ',id',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'base_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'seo' => 'nullable|array',
        ];
    }
}