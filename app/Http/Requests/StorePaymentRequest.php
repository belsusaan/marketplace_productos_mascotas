<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'order_id'        => 'required|exists:orders,id',
            'method'            => 'required|string|in:cash,card,transfer',
            'transaction_ref'   => 'nullable|string|max:255',
        ];
    }
}
