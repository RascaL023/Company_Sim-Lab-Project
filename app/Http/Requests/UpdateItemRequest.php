<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Adjust based on your auth logic
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('items')->ignore($this->item)],
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|in:alat,bahan',
            'unit' => 'sometimes|required|string|max:30',
            'stock_quantity' => 'sometimes|required|numeric|min:0',
            'minimum_stock' => 'sometimes|required|numeric|min:0',
            'location' => 'sometimes|nullable|string|max:100',
            'condition_status' => 'sometimes|required|string|in:baik,rusak,maintenance,kadaluarsa',
            'manufacturer' => 'sometimes|nullable|string|max:100',
            'serial_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('items')->ignore($this->item)],
            'purchase_date' => 'sometimes|nullable|date',
            'expiry_date' => 'sometimes|nullable|date',
            'next_calibration_date' => 'sometimes|nullable|date',
            'description' => 'sometimes|nullable|string',
            'created_by' => 'sometimes|required|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'code.unique' => 'Kode item sudah digunakan.',
            'serial_number.unique' => 'Nomor seri sudah digunakan.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add the item ID to the request so Rule::unique can ignore it
        if ($this->route('item')) {
            $this->merge([
                'item' => $this->route('item')->id,
            ]);
        }
    }
}
