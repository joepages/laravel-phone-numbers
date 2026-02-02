<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Helpers;

use Illuminate\Database\Eloquent\Model;
use PhoneNumbers\Concerns\HasPhoneNumbers;

/**
 * A dummy model for testing the HasPhoneNumbers trait.
 */
class TestModel extends Model
{
    use HasPhoneNumbers;

    protected $table = 'test_models';

    protected $guarded = [];
}
