<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, |array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_address' => 'required|string|max:500',
            'notes'            => 'nullable|string|max:500',
        ];
    }
}
