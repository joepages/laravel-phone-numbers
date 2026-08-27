<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Feature;

use Illuminate\Http\Request;
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Models\PhoneNumber;
use PhoneNumbers\Tests\Helpers\TestModel;
use PhoneNumbers\Tests\Helpers\TestPhoneNumberController;
use PhoneNumbers\Tests\TestCase;

/**
 * Covers the bulk-sync payload semantics:
 *
 * - key absent  => leave the collection alone
 * - [{...}]     => make the collection exactly this
 * - []          => remove them all
 */
class PhoneNumberSyncTest extends TestCase
{
    private PhoneNumberServiceInterface $service;

    private TestPhoneNumberController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PhoneNumberServiceInterface::class);
        $this->controller = new TestPhoneNumberController();
    }

    public function test_an_empty_payload_removes_the_last_phone_number(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $this->storePhoneNumber($parent, '5551234567');

        $this->controller->callAttachPhoneNumber(
            Request::create('/parents/1', 'PUT', ['phone_numbers' => []]),
            $parent
        );

        $this->assertCount(0, $this->service->getForParent($parent));
    }

    public function test_a_partial_payload_removes_only_the_omitted_phone_numbers(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $kept = $this->storePhoneNumber($parent, '5551234567');
        $dropped = $this->storePhoneNumber($parent, '5559876543');

        $this->controller->callAttachPhoneNumber(
            Request::create('/parents/1', 'PUT', ['phone_numbers' => [
                [
                    'id' => $kept->id,
                    'type' => 'mobile',
                    'country_code' => '+1:US',
                    'number' => '5551234567',
                ],
            ]]),
            $parent
        );

        $this->assertNotNull(PhoneNumber::find($kept->id));
        $this->assertNull(PhoneNumber::find($dropped->id));
    }

    public function test_an_absent_key_leaves_the_phone_numbers_alone(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $phoneNumber = $this->storePhoneNumber($parent, '5551234567');

        $this->controller->callAttachPhoneNumber(
            Request::create('/parents/1', 'PUT', ['name' => 'Renamed Parent']),
            $parent
        );

        $this->assertNotNull(PhoneNumber::find($phoneNumber->id));
    }

    public function test_an_explicit_null_is_ignored(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $phoneNumber = $this->storePhoneNumber($parent, '5551234567');

        $this->controller->callAttachPhoneNumber(
            Request::create('/parents/1', 'PUT', ['phone_numbers' => null]),
            $parent
        );

        $this->assertNotNull(PhoneNumber::find($phoneNumber->id));
    }

    public function test_syncing_an_empty_array_directly_clears_the_phone_numbers(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $this->storePhoneNumber($parent, '5551234567');
        $this->storePhoneNumber($parent, '5559876543');

        $result = $this->service->sync($parent, []);

        $this->assertCount(0, $result);
        $this->assertCount(0, $this->service->getForParent($parent));
    }

    public function test_syncing_an_empty_array_leaves_other_parents_alone(): void
    {
        $parent = TestModel::create(['name' => 'Test Parent']);
        $this->storePhoneNumber($parent, '5551234567');

        $otherParent = TestModel::create(['name' => 'Other Parent']);
        $otherPhoneNumber = $this->storePhoneNumber($otherParent, '5550000000');

        $this->service->sync($parent, []);

        $this->assertCount(0, $this->service->getForParent($parent));
        $this->assertNotNull(PhoneNumber::find($otherPhoneNumber->id));
    }

    private function storePhoneNumber(TestModel $parent, string $number): PhoneNumber
    {
        return $this->service->store($parent, new PhoneNumberDto(
            type: 'mobile',
            countryCode: '+1:US',
            number: $number,
        ));
    }
}
