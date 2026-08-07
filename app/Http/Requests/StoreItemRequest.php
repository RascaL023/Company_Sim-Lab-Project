<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'code' => 'required|string|max:50|unique:items,code',
            'name' => 'required|string|max:150',
            'type' => 'required|string|in:alat,bahan',
            'unit' => 'required|string|max:30',
            'stock_quantity' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'condition_status' => 'required|string|in:baik,rusak,maintenance,kadaluarsa',
            'manufacturer' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:items,serial_number',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date',
            'description' => 'nullable|string',
            'created_by' => 'required|exists:users,id',
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
}
