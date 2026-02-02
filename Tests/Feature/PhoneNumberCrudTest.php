<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Feature;

use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Models\PhoneNumber;
use PhoneNumbers\Tests\Helpers\TestModel;
use PhoneNumbers\Tests\TestCase;

class PhoneNumberCrudTest extends TestCase
{
    private PhoneNumberServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PhoneNumberServiceInterface::class);
    }

    public function test_it_creates_a_phone_number_for_a_model(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
            extension: null,
            formatted: '(555) 123-4567',
        );

        $phoneNumber = $this->service->store($parent, $dto);

        $this->assertInstanceOf(PhoneNumber::class, $phoneNumber);
        $this->assertEquals('+1', $phoneNumber->country_code);
        $this->assertEquals('5551234567', $phoneNumber->number);
        $this->assertEquals('(555) 123-4567', $phoneNumber->formatted);
        $this->assertEquals($parent->getMorphClass(), $phoneNumber->phoneable_type);
        $this->assertEquals($parent->id, $phoneNumber->phoneable_id);
    }

    public function test_it_updates_a_phone_number(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        );

        $phoneNumber = $this->service->store($parent, $dto);

        $updateDto = new PhoneNumberDto(
            type: 'work',
            countryCode: '+44',
            number: '2071234567',
        );

        $updated = $this->service->update($phoneNumber, $updateDto);

        $this->assertEquals('+44', $updated->country_code);
        $this->assertEquals('2071234567', $updated->number);
        $this->assertEquals('work', $updated->type);
    }

    public function test_it_deletes_a_phone_number(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $dto = new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        );

        $phoneNumber = $this->service->store($parent, $dto);
        $phoneNumberId = $phoneNumber->id;

        $result = $this->service->delete($phoneNumber);

        $this->assertTrue($result);
        $this->assertNull(PhoneNumber::find($phoneNumberId));
    }

    public function test_it_gets_all_phone_numbers_for_a_parent(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        ));

        $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5559876543',
        ));

        $phoneNumbers = $this->service->getForParent($parent);

        $this->assertCount(2, $phoneNumbers);
    }

    public function test_setting_primary_unsets_other_primaries(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber1 = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
            isPrimary: true,
        ));

        $this->assertTrue($phoneNumber1->is_primary);

        $phoneNumber2 = $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5559876543',
            isPrimary: true,
        ));

        $this->assertTrue($phoneNumber2->is_primary);
        $this->assertFalse($phoneNumber1->fresh()->is_primary);
    }

    public function test_it_syncs_phone_numbers(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        // Create initial phone numbers
        $phoneNumber1 = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        ));

        $phoneNumber2 = $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5559876543',
        ));

        // Sync: update phoneNumber1, drop phoneNumber2, add new phoneNumber3
        $result = $this->service->sync($parent, [
            [
                'id' => $phoneNumber1->id,
                'type' => 'mobile',
                'country_code' => '+1',
                'number' => '5551111111',
            ],
            [
                'type' => 'home',
                'country_code' => '+44',
                'number' => '2079999999',
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertNull(PhoneNumber::find($phoneNumber2->id));
        $this->assertEquals('5551111111', $phoneNumber1->fresh()->number);
    }

    public function test_has_phone_numbers_trait_relationships(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
            isPrimary: true,
        ));

        $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5559876543',
        ));

        $parent = $parent->fresh();

        $this->assertCount(2, $parent->phoneNumbers);
        $this->assertNotNull($parent->primaryPhoneNumber);
        $this->assertEquals('5551234567', $parent->primaryPhoneNumber->number);
        $this->assertCount(1, $parent->phoneNumbersOfType('work')->get());
    }

    public function test_mark_as_primary(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber1 = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
            isPrimary: true,
        ));

        $phoneNumber2 = $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5559876543',
        ));

        $phoneNumber2->markAsPrimary();

        $this->assertTrue($phoneNumber2->fresh()->is_primary);
        $this->assertFalse($phoneNumber1->fresh()->is_primary);
    }

    public function test_e164_attribute(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        ));

        $this->assertEquals('+15551234567', $phoneNumber->e164);
    }

    public function test_e164_attribute_strips_duplicate_plus(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+44',
            number: '2071234567',
        ));

        $this->assertEquals('+442071234567', $phoneNumber->e164);
    }

    public function test_full_number_attribute_uses_formatted(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
            formatted: '(555) 123-4567',
        ));

        $this->assertEquals('(555) 123-4567', $phoneNumber->full_number);
    }

    public function test_full_number_attribute_falls_back_to_e164(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber = $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1',
            number: '5551234567',
        ));

        $this->assertEquals('+15551234567', $phoneNumber->full_number);
    }

    public function test_full_number_attribute_includes_extension(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);

        $phoneNumber = $this->service->store($parent, new PhoneNumberDto(
            type: 'work',
            countryCode: '+1',
            number: '5551234567',
            extension: '456',
            formatted: '(555) 123-4567',
        ));

        $this->assertEquals('(555) 123-4567 ext. 456', $phoneNumber->full_number);
    }
}
