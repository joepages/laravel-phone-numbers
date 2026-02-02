<?php

declare(strict_types=1);

namespace PhoneNumbers\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhoneNumbers\Contracts\PhoneNumberRepositoryInterface;
use PhoneNumbers\Models\PhoneNumber;

class PhoneNumberRepository implements PhoneNumberRepositoryInterface
{
    public function __construct(
        protected PhoneNumber $model,
    ) {}

    public function find(int $id): ?PhoneNumber
    {
        return $this->model->find($id);
    }

    public function create(array $data): PhoneNumber
    {
        return $this->model->create($data);
    }

    public function update(PhoneNumber $phoneNumber, array $data): PhoneNumber
    {
        $phoneNumber->update($data);

        return $phoneNumber->fresh();
    }

    public function delete(PhoneNumber $phoneNumber): bool
    {
        return (bool) $phoneNumber->delete();
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->model
            ->where('phoneable_type', $parent->getMorphClass())
            ->where('phoneable_id', $parent->getKey())
            ->orderByDesc('is_primary')
            ->orderBy('type')
            ->get();
    }

    public function findForParent(int $phoneNumberId, Model $parent): ?PhoneNumber
    {
        return $this->model
            ->where('id', $phoneNumberId)
            ->where('phoneable_type', $parent->getMorphClass())
            ->where('phoneable_id', $parent->getKey())
            ->first();
    }

    public function unsetPrimaryForParent(Model $parent): void
    {
        $this->model
            ->where('phoneable_type', $parent->getMorphClass())
            ->where('phoneable_id', $parent->getKey())
            ->update(['is_primary' => false]);
    }

    public function deleteWhereNotIn(Model $parent, array $ids): void
    {
        $this->model
            ->where('phoneable_type', $parent->getMorphClass())
            ->where('phoneable_id', $parent->getKey())
            ->whereNotIn('id', $ids)
            ->delete();
    }
}
