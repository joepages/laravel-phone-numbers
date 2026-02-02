<?php

declare(strict_types=1);

namespace PhoneNumbers\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhoneNumbers\Models\PhoneNumber;

interface PhoneNumberRepositoryInterface
{
    public function find(int $id): ?PhoneNumber;

    public function create(array $data): PhoneNumber;

    public function update(PhoneNumber $phoneNumber, array $data): PhoneNumber;

    public function delete(PhoneNumber $phoneNumber): bool;

    public function getForParent(Model $parent): Collection;

    public function findForParent(int $phoneNumberId, Model $parent): ?PhoneNumber;

    public function unsetPrimaryForParent(Model $parent): void;

    public function deleteWhereNotIn(Model $parent, array $ids): void;
}
