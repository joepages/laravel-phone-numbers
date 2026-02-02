<?php

declare(strict_types=1);

namespace PhoneNumbers\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use PhoneNumbers\Models\PhoneNumber;

/**
 * Trait to add to any Eloquent model that can have phone numbers.
 *
 * Usage:
 *   use PhoneNumbers\Concerns\HasPhoneNumbers;
 *
 *   class Facility extends Model {
 *       use HasPhoneNumbers;
 *   }
 */
trait HasPhoneNumbers
{
    public function phoneNumbers(): MorphMany
    {
        return $this->morphMany(PhoneNumber::class, 'phoneable');
    }

    public function primaryPhoneNumber(): MorphOne
    {
        return $this->morphOne(PhoneNumber::class, 'phoneable')
            ->where('is_primary', true);
    }

    public function phoneNumbersOfType(string $type): MorphMany
    {
        return $this->phoneNumbers()->where('type', $type);
    }
}
