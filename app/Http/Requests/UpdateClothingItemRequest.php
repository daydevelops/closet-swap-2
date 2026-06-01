<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClothingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'sometimes|string|max:140',
            'description' => 'sometimes|string|max:1000',
            'type'        => 'sometimes|exists:ci_types,id',
            'gender'      => 'sometimes|exists:ci_genders,id',
            'size'        => 'sometimes|exists:ci_sizes,id',
            'fit'          => 'sometimes|exists:ci_fits,id',
            'condition'   => 'sometimes|exists:ci_conditions,id',
            'units'       => 'nullable|exists:ci_units,id',
            'brand'       => 'sometimes|string|max:255',
            'tags'        => 'sometimes|array',
            'colors'      => 'sometimes|array',
            'materials'   => 'sometimes|array',
        ];
    }
}
