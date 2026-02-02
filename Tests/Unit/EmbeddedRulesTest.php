<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Unit;

use PhoneNumbers\Http\Requests\PhoneNumberRequest;
use PhoneNumbers\Tests\UnitTestCase;

class EmbeddedRulesTest extends UnitTestCase
{
    public function test_it_returns_rules_with_default_prefix(): void
    {
        $rules = PhoneNumberRequest::embeddedRules();

        $this->assertArrayHasKey('phone_numbers', $rules);
        $this->assertArrayHasKey('phone_numbers.*.country_code', $rules);
        $this->assertArrayHasKey('phone_numbers.*.number', $rules);
        $this->assertArrayHasKey('phone_numbers.*.id', $rules);
        $this->assertArrayHasKey('phone_numbers.*.type', $rules);
        $this->assertArrayHasKey('phone_numbers.*.is_primary', $rules);
        $this->assertArrayHasKey('phone_numbers.*.is_verified', $rules);
        $this->assertArrayHasKey('phone_numbers.*.extension', $rules);
        $this->assertArrayHasKey('phone_numbers.*.formatted', $rules);
        $this->assertArrayHasKey('phone_numbers.*.metadata', $rules);
    }

    public function test_it_returns_rules_with_custom_prefix(): void
    {
        $rules = PhoneNumberRequest::embeddedRules('contact_phones');

        $this->assertArrayHasKey('contact_phones', $rules);
        $this->assertArrayHasKey('contact_phones.*.country_code', $rules);
        $this->assertArrayHasKey('contact_phones.*.number', $rules);

        // Ensure default prefix keys are not present
        $this->assertArrayNotHasKey('phone_numbers', $rules);
        $this->assertArrayNotHasKey('phone_numbers.*.country_code', $rules);
    }

    public function test_top_level_rule_is_sometimes_array(): void
    {
        $rules = PhoneNumberRequest::embeddedRules();

        $this->assertEquals(['sometimes', 'array'], $rules['phone_numbers']);
    }

    public function test_required_fields_have_required_rule(): void
    {
        $rules = PhoneNumberRequest::embeddedRules();

        $this->assertContains('required', $rules['phone_numbers.*.country_code']);
        $this->assertContains('required', $rules['phone_numbers.*.number']);
    }

    public function test_id_field_is_optional_integer(): void
    {
        $rules = PhoneNumberRequest::embeddedRules();

        $this->assertContains('sometimes', $rules['phone_numbers.*.id']);
        $this->assertContains('integer', $rules['phone_numbers.*.id']);
        $this->assertContains('exists:phone_numbers,id', $rules['phone_numbers.*.id']);
    }
}
