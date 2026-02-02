<?php

declare(strict_types=1);

namespace PhoneNumbers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;
use PhoneNumbers\Http\Requests\PhoneNumberRequest;
use PhoneNumbers\Http\Resources\PhoneNumberCollection;
use PhoneNumbers\Http\Resources\PhoneNumberResource;

/**
 * Controller trait for managing phone numbers on a parent model.
 *
 * Provides:
 * - attachPhoneNumber(): called by BaseApiController::attachRelatedData() for bulk sync
 * - storePhoneNumber(), updatePhoneNumber(), deletePhoneNumber(), listPhoneNumbers(): standalone CRUD endpoints
 *
 * The consuming controller MUST define:
 * - $modelClass (string): The parent model class
 * - $serviceInterface: The parent model's service interface
 */
trait ManagesPhoneNumbers
{
    /**
     * List all phone numbers for a parent model.
     */
    public function listPhoneNumbers(int $parentId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('view', $parent);

        $phoneNumberService = app(PhoneNumberServiceInterface::class);
        $phoneNumbers = $phoneNumberService->getForParent($parent);

        return new PhoneNumberCollection($phoneNumbers);
    }

    /**
     * Store a new phone number for a parent model.
     */
    public function storePhoneNumber(PhoneNumberRequest $request, int $parentId): JsonResponse
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $dto = PhoneNumberDto::fromRequest($request);
        $phoneNumberService = app(PhoneNumberServiceInterface::class);
        $phoneNumber = $phoneNumberService->store($parent, $dto);

        return (new PhoneNumberResource($phoneNumber))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an existing phone number for a parent model.
     */
    public function updatePhoneNumber(PhoneNumberRequest $request, int $parentId, int $phoneNumberId): JsonResource
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $phoneNumberService = app(PhoneNumberServiceInterface::class);
        $phoneNumber = $phoneNumberService->findForParent($phoneNumberId, $parent);

        if (! $phoneNumber) {
            abort(404, 'Phone number not found.');
        }

        $dto = PhoneNumberDto::fromRequest($request);
        $phoneNumber = $phoneNumberService->update($phoneNumber, $dto);

        return new PhoneNumberResource($phoneNumber);
    }

    /**
     * Delete a phone number for a parent model.
     */
    public function deletePhoneNumber(int $parentId, int $phoneNumberId): JsonResponse
    {
        $parent = $this->resolveParentModel($parentId);

        $this->authorize('update', $parent);

        $phoneNumberService = app(PhoneNumberServiceInterface::class);
        $phoneNumber = $phoneNumberService->findForParent($phoneNumberId, $parent);

        if (! $phoneNumber) {
            abort(404, 'Phone number not found.');
        }

        $phoneNumberService->delete($phoneNumber);

        return response()->json(['message' => 'Phone number deleted successfully.'], 200);
    }

    /**
     * Called by BaseApiController::attachRelatedData() during store/update.
     * Supports bulk sync: if 'phone_numbers' key exists in request, syncs all phone numbers.
     */
    protected function attachPhoneNumber(Request $request, Model $model): void
    {
        if (! $request->has('phone_numbers')) {
            return;
        }

        $phoneNumbersData = $request->input('phone_numbers', []);

        if (empty($phoneNumbersData)) {
            return;
        }

        $phoneNumberService = app(PhoneNumberServiceInterface::class);
        $phoneNumberService->sync($model, $phoneNumbersData);
    }

    /**
     * Resolve the parent model by ID.
     */
    protected function resolveParentModel(int $parentId): Model
    {
        return $this->service->getById($parentId);
    }
}
