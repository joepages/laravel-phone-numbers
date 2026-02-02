<?php

declare(strict_types=1);

namespace PhoneNumbers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneNumberResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_primary' => $this->is_primary,
            'country_code' => $this->country_code,
            'number' => $this->number,
            'extension' => $this->extension,
            'formatted' => $this->formatted,
            'e164' => $this->e164,
            'full_number' => $this->full_number,
            'is_verified' => $this->is_verified,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
