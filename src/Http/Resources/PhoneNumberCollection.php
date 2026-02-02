<?php

declare(strict_types=1);

namespace PhoneNumbers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PhoneNumberCollection extends ResourceCollection
{
    public $collects = PhoneNumberResource::class;

    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
