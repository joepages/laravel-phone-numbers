<?php

declare(strict_types=1);

namespace PhoneNumbers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhoneNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $typeRule = config('phone-numbers.allow_custom_types', true)
            ? 'string|max:50'
            : 'string|in:' . implode(',', config('phone-numbers.types', []));

        return [
            'type' => ['sometimes', $typeRule],
            'is_primary' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
            'country_code' => ['required', 'string', 'max:5'],
            'number' => ['required', 'string', 'max:20'],
            'extension' => ['nullable', 'string', 'max:10'],
            'formatted' => ['nullable', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
