<?php

declare(strict_types=1);

namespace PhoneNumbers\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PhoneNumbers\Contracts\PhoneNumberRepositoryInterface;
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Models\PhoneNumber;

class PhoneNumberService implements PhoneNumberServiceInterface
{
    public function __construct(
        protected PhoneNumberRepositoryInterface $repository,
    ) {}

    public function store(Model $parent, PhoneNumberDto $dto): PhoneNumber
    {
        $data = array_merge($dto->toArray(), [
            'phoneable_type' => $parent->getMorphClass(),
            'phoneable_id' => $parent->getKey(),
        ]);

        if ($dto->isPrimary) {
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->create($data);
    }

    public function update(PhoneNumber $phoneNumber, PhoneNumberDto $dto): PhoneNumber
    {
        $data = $dto->toArray();

        if ($dto->isPrimary && ! $phoneNumber->is_primary) {
            $parent = $phoneNumber->phoneable;
            $this->repository->unsetPrimaryForParent($parent);
        }

        return $this->repository->update($phoneNumber, $data);
    }

    public function delete(PhoneNumber $phoneNumber): bool
    {
        return $this->repository->delete($phoneNumber);
    }

    public function getForParent(Model $parent): Collection
    {
        return $this->repository->getForParent($parent);
    }

    public function findForParent(int $phoneNumberId, Model $parent): ?PhoneNumber
    {
        return $this->repository->findForParent($phoneNumberId, $parent);
    }

    /**
     * Sync phone numbers for a parent model.
     * Creates new entries, updates existing (matched by id), deletes missing.
     */
    public function sync(Model $parent, array $phoneNumbersData): Collection
    {
        $keptIds = [];

        foreach ($phoneNumbersData as $phoneNumberData) {
            $dto = PhoneNumberDto::fromArray($phoneNumberData);

            if (isset($phoneNumberData['id'])) {
                // Update existing
                $phoneNumber = $this->findForParent((int) $phoneNumberData['id'], $parent);
                if ($phoneNumber) {
                    $this->update($phoneNumber, $dto);
                    $keptIds[] = $phoneNumber->id;

                    continue;
                }
            }

            // Create new
            $phoneNumber = $this->store($parent, $dto);
            $keptIds[] = $phoneNumber->id;
        }

        // Delete phone numbers not in the payload
        if (! empty($keptIds)) {
            $this->repository->deleteWhereNotIn($parent, $keptIds);
        }

        return $this->getForParent($parent);
    }
}
