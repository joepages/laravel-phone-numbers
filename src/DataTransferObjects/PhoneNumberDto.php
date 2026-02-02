<?php

declare(strict_types=1);

namespace PhoneNumbers\DataTransferObjects;

use PhoneNumbers\Http\Requests\PhoneNumberRequest;

readonly class PhoneNumberDto
{
    public function __construct(
        public string $type,
        public string $countryCode,
        public string $number,
        public ?string $extension = null,
        public ?string $formatted = null,
        public bool $isPrimary = false,
        public bool $isVerified = false,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(PhoneNumberRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? config('phone-numbers.default_type', 'mobile'),
            countryCode: $data['country_code'],
            number: $data['number'],
            extension: $data['extension'] ?? null,
            formatted: $data['formatted'] ?? null,
            isPrimary: (bool) ($data['is_primary'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'country_code' => $this->countryCode,
            'number' => $this->number,
            'extension' => $this->extension,
            'formatted' => $this->formatted,
            'is_primary' => $this->isPrimary,
            'is_verified' => $this->isVerified,
            'metadata' => $this->metadata,
        ];
    }
}
