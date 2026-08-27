<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use PhoneNumbers\Concerns\ManagesPhoneNumbers;

/**
 * Minimal host for the ManagesPhoneNumbers trait.
 *
 * Only exists so the protected attachPhoneNumber() hook can be exercised the
 * way BaseApiController::attachRelatedData() calls it, without standing up a
 * full controller stack.
 */
class TestPhoneNumberController
{
    use ManagesPhoneNumbers;

    public function callAttachPhoneNumber(Request $request, Model $model): void
    {
        $this->attachPhoneNumber($request, $model);
    }
}
