<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClothingItemRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:140',
            'description' => 'required|string|max:1000',
            'type' => 'required|exists:ci_types,id',
            'size' => 'required|exists:ci_sizes,id',
            'fit' => 'required|exists:ci_fits,id',
            'condition' => 'required|exists:ci_conditions,id',
            'units' => 'nullable|exists:ci_units,id',
            'tags' => 'array',
            'colors' => 'array',
            'materials' => 'array',

            'pictures' => 'required|array|min:1',
            'pictures.*' => 'file|mimes:jpg,jpeg,png,PNG,webp|max:5120', // max is in kilobytes = 5MB
        ];
    }
}
