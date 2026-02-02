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
        $typeRules = config('phone-numbers.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('phone-numbers.types', []))];

        return [
            'type' => ['sometimes', ...$typeRules],
            'is_primary' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
            'country_code' => ['required', 'string', 'max:5'],
            'number' => ['required', 'string', 'max:20'],
            'extension' => ['nullable', 'string', 'max:10'],
            'formatted' => ['nullable', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Embeddable rules for parent requests.
     *
     * Usage: ...PhoneNumberRequest::embeddedRules() in a parent FormRequest::rules()
     */
    public static function embeddedRules(string $prefix = 'phone_numbers'): array
    {
        $typeRules = config('phone-numbers.allow_custom_types', true)
            ? ['string', 'max:50']
            : ['string', 'in:' . implode(',', config('phone-numbers.types', []))];

        return [
            $prefix => ['sometimes', 'array'],
            "{$prefix}.*.id" => ['sometimes', 'integer', 'exists:phone_numbers,id'],
            "{$prefix}.*.type" => ['sometimes', ...$typeRules],
            "{$prefix}.*.is_primary" => ['sometimes', 'boolean'],
            "{$prefix}.*.is_verified" => ['sometimes', 'boolean'],
            "{$prefix}.*.country_code" => ['required', 'string', 'max:5'],
            "{$prefix}.*.number" => ['required', 'string', 'max:20'],
            "{$prefix}.*.extension" => ['nullable', 'string', 'max:10'],
            "{$prefix}.*.formatted" => ['nullable', 'string', 'max:30'],
            "{$prefix}.*.metadata" => ['nullable', 'array'],
        ];
    }
}
