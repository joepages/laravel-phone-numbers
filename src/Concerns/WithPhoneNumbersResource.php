<?php

declare(strict_types=1);

namespace PhoneNumbers\Concerns;

use PhoneNumbers\Http\Resources\PhoneNumberResource;

/**
 * Trait for API Resources to include phone numbers in the response.
 *
 * Usage:
 *   class FacilityResource extends BaseResource {
 *       use WithPhoneNumbersResource;
 *
 *       public function toArray($request): array {
 *           return array_merge([
 *               'id' => $this->id,
 *               'name' => $this->name,
 *           ], $this->phoneNumbersResource());
 *       }
 *   }
 */
trait WithPhoneNumbersResource
{
    protected function phoneNumbersResource(): array
    {
        return [
            'phone_numbers' => PhoneNumberResource::collection($this->whenLoaded('phoneNumbers')),
            'primary_phone_number' => $this->whenLoaded('primaryPhoneNumber', function () {
                return $this->primaryPhoneNumber ? new PhoneNumberResource($this->primaryPhoneNumber) : null;
            }),
        ];
    }
}
