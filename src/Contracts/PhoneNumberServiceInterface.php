<?php

declare(strict_types=1);

namespace PhoneNumbers\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Models\PhoneNumber;

interface PhoneNumberServiceInterface
{
    public function store(Model $parent, PhoneNumberDto $dto): PhoneNumber;

    public function update(PhoneNumber $phoneNumber, PhoneNumberDto $dto): PhoneNumber;

    public function delete(PhoneNumber $phoneNumber): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $phoneNumberId, Model $parent): ?PhoneNumber;

    /**
     * Sync phone numbers for a parent model.
     * Creates new, updates existing (matched by id), deletes missing.
     *
     * @param  array<int, array>  $phoneNumbersData
     */
    public function sync(Model $parent, array $phoneNumbersData): Collection;
}
