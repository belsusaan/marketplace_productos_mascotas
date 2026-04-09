<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id'=> 'required|exists:categories,id',
            'name'=> 'required|string|max:255',
            'description'=> 'nullable|string',
            'price'=> 'required|numeric|min:0',
            'stock'=> 'required|integer|min:0',
            'image_url'=> 'nullable|url',
            'is_active'=> 'nullable|boolean',
        ];
    }
}
