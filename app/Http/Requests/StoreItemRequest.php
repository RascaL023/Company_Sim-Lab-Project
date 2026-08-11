<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure type is removed so it's not duplicated in validated data
        if ($this->has('type')) {
            $this->request->remove('type');
        }
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

        // Enforce domain rules for stock_quantity, minimum_stock, location_id based on item type
        if (isset($data['category_id'])) {
            $category = Category::find($data['category_id']);
            if ($category) {
                if ($category->type === 'alat') {
                    // For alat, these must be null
                    $data['stock_quantity'] = null;
                    $data['minimum_stock'] = null;
                    $data['location_id'] = null;
                } elseif ($category->type === 'bahan') {
                    // For bahan, these must be present and valid
                    // We'll validate that they are not null and meet the conditions
                    // If they are null, we'll add errors
                    if (! isset($data['stock_quantity']) || $data['stock_quantity'] === null) {
                        $this->getValidatorInstance()->errors()->add('stock_quantity', 'Stock quantity wajib diisi untuk bahan.');
                    }
                    if (! isset($data['minimum_stock']) || $data['minimum_stock'] === null) {
                        $this->getValidatorInstance()->errors()->add('minimum_stock', 'Minimum stock wajib diisi untuk bahan.');
                    }
                    if (! isset($data['location_id']) || $data['location_id'] === null) {
                        $this->getValidatorInstance()->errors()->add('location_id', 'Lokasi wajib diisi untuk bahan.');
                    }

                    // If there are errors, we should throw a validation exception
                    if ($this->getValidatorInstance()->errors()->any()) {
                        throw new ValidationException($this->getValidatorInstance());
                    }
                }
            }
        }

        return $data;
    }
}
