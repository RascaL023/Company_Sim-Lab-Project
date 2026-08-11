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
            // type diambil dari category; boleh dikirim FE untuk UX, diabaikan saat persist
            'type' => 'nullable|string|in:alat,bahan',
            'unit' => 'required|string|max:30',
            'stock_quantity' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'location_id' => 'nullable|exists:locations,id',
            'manufacturer' => 'nullable|string|max:100',
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
            'location_id.exists' => 'Lokasi tidak ditemukan.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        unset($data['type']);

        return $data;
    }
}
