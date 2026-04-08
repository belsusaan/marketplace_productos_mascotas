<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')->id;
        return [
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $categoryId,
            'description' => 'nullable|string',
            'slug'=> 'nullable|string|max:255|unique:categories,slug,' . $categoryId,
            'image_url'=> 'nullable|url',
            'is_active'=> 'nullable|boolean',
        ];
    }
}
