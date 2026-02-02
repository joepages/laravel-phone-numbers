<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Unit;

use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Tests\UnitTestCase;

class PhoneNumberDtoTest extends UnitTestCase
{
    public function test_it_creates_dto_from_array(): void
    {
        $data = [
            'type' => 'mobile',
            'country_code' => '+1:US',
            'number' => '5551234567',
            'extension' => '123',
            'formatted' => '(555) 123-4567',
            'is_primary' => true,
            'is_verified' => true,
            'metadata' => ['notes' => 'Personal cell'],
        ];

        $dto = PhoneNumberDto::fromArray($data);

        $this->assertEquals('mobile', $dto->type);
        $this->assertEquals('+1:US', $dto->countryCode);
        $this->assertEquals('5551234567', $dto->number);
        $this->assertEquals('123', $dto->extension);
        $this->assertEquals('(555) 123-4567', $dto->formatted);
        $this->assertTrue($dto->isPrimary);
        $this->assertTrue($dto->isVerified);
        $this->assertEquals(['notes' => 'Personal cell'], $dto->metadata);
    }

    public function test_it_converts_to_array(): void
    {
        $dto = new PhoneNumberDto(
            type: 'work',
            countryCode: '+44:GB',
            number: '2071234567',
        );

        $array = $dto->toArray();

        $this->assertEquals('work', $array['type']);
        $this->assertEquals('+44:GB', $array['country_code']);
        $this->assertEquals('2071234567', $array['number']);
        $this->assertFalse($array['is_primary']);
        $this->assertFalse($array['is_verified']);
        $this->assertNull($array['extension']);
        $this->assertNull($array['formatted']);
        $this->assertNull($array['metadata']);
    }

    public function test_it_uses_default_type_from_config(): void
    {
        config(['phone-numbers.default_type' => 'work']);

        $data = [
            'country_code' => '+1:US',
            'number' => '5559876543',
        ];

        $dto = PhoneNumberDto::fromArray($data);

        $this->assertEquals('work', $dto->type);
    }

    public function test_is_primary_defaults_to_false(): void
    {
        $data = [
            'type' => 'mobile',
            'country_code' => '+1:US',
            'number' => '5551234567',
        ];

        $dto = PhoneNumberDto::fromArray($data);

        $this->assertFalse($dto->isPrimary);
    }

    public function test_is_verified_defaults_to_false(): void
    {
        $data = [
            'type' => 'mobile',
            'country_code' => '+1:US',
            'number' => '5551234567',
        ];

        $dto = PhoneNumberDto::fromArray($data);

        $this->assertFalse($dto->isVerified);
    }

    public function test_it_is_readonly(): void
    {
        $dto = new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1:US',
            number: '5551234567',
        );

        $this->expectException(\Error::class);
        $dto->type = 'work'; // @phpstan-ignore-line
    }
}
