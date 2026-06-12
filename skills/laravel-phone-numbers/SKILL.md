---
name: laravel-phone-numbers
description: Polymorphic phone number storage for Laravel — attach N phone numbers to any Eloquent model (Customer, Order, Facility, User, contact, vendor) with primary-number management, E.164 output, compound country/dial codes (+1:US), types (mobile/home/work/fax), verification flags, REST CRUD endpoints, bulk sync, validation rules, and stancl/tenancy-aware migrations. Use this skill whenever a task involves storing/validating/formatting phone numbers, phone number columns or tables, E.164, country codes or dial codes, primary phone selection, phone CRUD APIs, syncing phone lists from a form payload, or adding phone numbers to API resources — even if the package joepages/laravel-phone-numbers is not named.
---

# Laravel Phone Numbers

Polymorphic phone numbers for Laravel 11/12: a single `phone_numbers` table holds any number of phones for any Eloquent model via a `phoneable` morph. The core abstractions are the `HasPhoneNumbers` model trait (relationships), the `PhoneNumberServiceInterface` (store/update/delete/sync with primary-number bookkeeping), and the `ManagesPhoneNumbers` controller trait (drop-in REST endpoints). Mental model: "morphMany phone numbers + a service that keeps exactly one `is_primary` per parent."

## Installation & setup

```bash
composer require joepages/laravel-phone-numbers
```

If your project resolves this package from a VCS repository instead of Packagist, register it first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/joepages/laravel-phone-numbers" }
]
```

The service provider (`PhoneNumbers\PhoneNumbersServiceProvider`) is auto-discovered. Then:

```bash
php artisan phone-numbers:install   # publishes config (and migrations, tenancy-aware)
php artisan migrate
```

- `phone-numbers:install --force` overwrites existing published files; `--skip-migrations` publishes only the config.
- Package migrations are also auto-loaded via `loadMigrationsFrom()`, so plain `php artisan migrate` creates `phone_numbers` even without publishing. The installer copies the migration into `database/migrations/` (or `database/migrations/tenant/` when multi-tenancy is detected) so you own a local copy.
- Config alone can be published with `php artisan vendor:publish --tag=phone-numbers-config`.
- No env vars required. No external phone library (no libphonenumber) — formatting is string-based.

### Install this skill into Claude Code

This package ships this skill at `skills/laravel-phone-numbers/`. Add to your project `composer.json` so the skill lands in `.claude/skills/` on every install/update:

```json
"scripts": {
    "post-install-cmd": ["@php vendor/joepages/laravel-phone-numbers/bin/install-skill"],
    "post-update-cmd": ["@php vendor/joepages/laravel-phone-numbers/bin/install-skill"]
}
```

The installer overwrites on every run (the package copy is the source of truth) and no-ops unless your project root contains a `.claude/` directory. Add `.claude/skills/laravel-phone-numbers/` to your project `.gitignore`.

## Core API

All classes live under the root namespace `PhoneNumbers\` (PSR-4 root is `src/`).

### Config (`config/phone-numbers.php`)

| Key | Default | Meaning |
|---|---|---|
| `tenancy_mode` | `'auto'` | `'auto'` detects stancl/tenancy (`tenancy()` function + `tenancy.tenant_model` config); `'single'` / `'multi'` force the mode. Only affects where `phone-numbers:install` publishes migrations. |
| `types` | `['mobile', 'home', 'work', 'fax', 'other']` | Allowed `type` values when `allow_custom_types` is `false`. |
| `default_type` | `'mobile'` | `type` used when a payload omits it (applied by `PhoneNumberDto::fromArray()`). |
| `allow_custom_types` | `true` | `true`: any string type up to 50 chars validates. `false`: validation uses `in:` against `types`. |

### Model: `PhoneNumbers\Models\PhoneNumber`

Table `phone_numbers`. Fillable: `phoneable_type`, `phoneable_id`, `type`, `is_primary`, `country_code`, `number`, `extension`, `formatted`, `is_verified`, `metadata`. Casts: `is_primary`/`is_verified` → bool, `metadata` → array. No soft deletes. Has a factory.

| Member | Signature | Purpose |
|---|---|---|
| `phoneable()` | `(): MorphTo` | Parent model. |
| `scopePrimary` | `PhoneNumber::primary()` | Only `is_primary = true`. |
| `scopeOfType` | `PhoneNumber::ofType(string $type)` | Filter by `type`. |
| `scopeForModel` | `PhoneNumber::forModel(Model $model)` | All rows for one parent (morph class + key). |
| `scopeVerified` | `PhoneNumber::verified()` | Only `is_verified = true`. |
| `markAsPrimary()` | `(): bool` | Sets this row primary; mass-updates siblings of the same parent to `is_primary = false`. |
| `e164` accessor | `->e164: string` | `+{dialCode}{number}`. Compound `country_code` like `"+1:US"` uses the part before `:`. Pure concatenation — no digit validation. |
| `dial_code` accessor | `->dial_code: string` | `"+1"` from `"+1:US"`; plain codes returned as-is. |
| `iso_country_code` accessor | `->iso_country_code: ?string` | `"US"` from `"+1:US"`; `null` for plain codes like `"+1"`. |
| `full_number` accessor | `->full_number: string` | `formatted` if set, else `e164`; appends `" ext. {extension}"` when extension present. |

`country_code` formats: compound `"+1:US"` (recommended — disambiguates US/CA which share `+1`) or plain `"+1"` (legacy; `iso_country_code` is then `null`). Max length 10.

### Model trait: `PhoneNumbers\Concerns\HasPhoneNumbers`

| Method | Returns | Purpose |
|---|---|---|
| `phoneNumbers()` | `MorphMany` | All phone numbers. |
| `primaryPhoneNumber()` | `MorphOne` | The row with `is_primary = true` (or null). |
| `phoneNumbersOfType(string $type)` | `MorphMany` | Filtered by `type`. |

### Service: `PhoneNumbers\Contracts\PhoneNumberServiceInterface`

Resolve via `app(PhoneNumberServiceInterface::class)` or constructor injection (registered as a singleton; implemented by `PhoneNumbers\Services\PhoneNumberService`).

| Method | Signature | Behavior |
|---|---|---|
| `store` | `(Model $parent, PhoneNumberDto $dto): PhoneNumber` | Creates a row for the parent. If `$dto->isPrimary`, first unsets primary on all of the parent's other numbers. |
| `update` | `(PhoneNumber $phoneNumber, PhoneNumberDto $dto): PhoneNumber` | Full replace with the DTO's fields (see edge cases). Unsets sibling primaries when promoting to primary. Returns a fresh model. |
| `delete` | `(PhoneNumber $phoneNumber): bool` | Hard delete. |
| `getForParent` | `(Model $parent): Collection` | All numbers for the parent, ordered `is_primary` desc, then `type` asc. |
| `findForParent` | `(int $phoneNumberId, Model $parent): ?PhoneNumber` | The row only if it belongs to that parent; otherwise `null`. |
| `sync` | `(Model $parent, array $phoneNumbersData): Collection` | Per item: has `id` belonging to parent → update; else → create. Rows absent from the payload are deleted. Returns the resulting collection. An empty array deletes nothing. |

### Repository: `PhoneNumbers\Contracts\PhoneNumberRepositoryInterface`

Lower-level data access (bound to `PhoneNumbers\Repositories\PhoneNumberRepository`; prefer the service): `find(int $id): ?PhoneNumber`, `create(array $data): PhoneNumber`, `update(PhoneNumber $phoneNumber, array $data): PhoneNumber`, `delete(PhoneNumber $phoneNumber): bool`, `getForParent(Model $parent): Collection`, `findForParent(int $phoneNumberId, Model $parent): ?PhoneNumber`, `unsetPrimaryForParent(Model $parent): void`, `deleteWhereNotIn(Model $parent, array $ids): void`.

### DTO: `PhoneNumbers\DataTransferObjects\PhoneNumberDto` (readonly)

```php
new PhoneNumberDto(
    string $type,                  // e.g. 'mobile'
    string $countryCode,           // e.g. '+1:US' or '+1'
    string $number,                // e.g. '5551234567'
    ?string $extension = null,
    ?string $formatted = null,     // display string, e.g. '(555) 123-4567'
    bool $isPrimary = false,
    bool $isVerified = false,
    ?array $metadata = null,
);
```

- `PhoneNumberDto::fromArray(array $data): self` — snake_case keys (`country_code`, `is_primary`, …); `type` falls back to `config('phone-numbers.default_type')`; `country_code` and `number` keys are REQUIRED (missing keys throw).
- `PhoneNumberDto::fromRequest(PhoneNumberRequest $request): self` — builds from `$request->validated()`.
- `toArray(): array` — snake_case array including nulls/falses for every field.

### Validation: `PhoneNumbers\Http\Requests\PhoneNumberRequest`

`authorize()` returns `true`. `rules()`: `country_code` required|string|max:10; `number` required|string|max:20; `extension` nullable|string|max:10; `formatted` nullable|string|max:30; `type` sometimes|string|max:50 (or `in:` list when `allow_custom_types` is false); `is_primary`, `is_verified` sometimes|boolean; `metadata` nullable|array.

`PhoneNumberRequest::embeddedRules(string $prefix = 'phone_numbers'): array` — static; returns the same rules namespaced as `{$prefix}` (sometimes|array) and `{$prefix}.*.{field}`, plus `{$prefix}.*.id` (sometimes|integer|exists:phone_numbers,id). Spread into a parent FormRequest's `rules()` for bulk-sync payloads.

### Controller trait: `PhoneNumbers\Concerns\ManagesPhoneNumbers`

Public endpoint methods (all resolve the parent, then call `$this->authorize(...)`):

| Method | Signature | Policy ability | Response |
|---|---|---|---|
| `listPhoneNumbers` | `(int $parentId): JsonResource` | `view` on parent | `PhoneNumberCollection` (wrapped in `data`) |
| `storePhoneNumber` | `(PhoneNumberRequest $request, int $parentId): JsonResponse` | `update` on parent | `PhoneNumberResource`, HTTP 201 |
| `updatePhoneNumber` | `(PhoneNumberRequest $request, int $parentId, int $phoneNumberId): JsonResource` | `update` on parent | `PhoneNumberResource`; 404 if the phone doesn't belong to the parent |
| `deletePhoneNumber` | `(int $parentId, int $phoneNumberId): JsonResponse` | `update` on parent | `{"message": "Phone number deleted successfully."}`, 200; 404 if not owned |

Protected integration hooks:

- `resolveParentModel(int $parentId): Model` — default implementation calls `$this->service->getById($parentId)`. Your controller must expose a `$service` with `getById()`, or override this method (e.g. `return Customer::findOrFail($parentId);`).
- `attachPhoneNumber(Request $request, Model $model): void` — bulk-sync hook: if the request has a non-empty `phone_numbers` array, calls `sync()`. The package never calls this itself; invoke it from your own store/update flow (e.g. a base controller's attach-related-data step).

The trait uses `$this->authorize()`, so the controller needs Laravel's `AuthorizesRequests` trait and a registered policy for the parent model.

### Route macro (registered by the service provider)

`Route::phoneNumberRoutes(string $prefix, string $controller)` registers, under `{$prefix}/{singular}` (parameter name is `Str::singular($prefix)`):

| Method | URI | Trait method |
|---|---|---|
| GET | `/{prefix}/{singular}/phone-numbers` | `listPhoneNumbers` |
| POST | `/{prefix}/{singular}/phone-numbers` | `storePhoneNumber` |
| PUT | `/{prefix}/{singular}/phone-numbers/{phoneNumber}` | `updatePhoneNumber` |
| DELETE | `/{prefix}/{singular}/phone-numbers/{phoneNumber}` | `deletePhoneNumber` |

Parameters arrive as plain ints (no route-model binding); apply middleware yourself by wrapping the macro call in a group.

### API resources

- `PhoneNumbers\Http\Resources\PhoneNumberResource` — fields: `id`, `type`, `is_primary`, `country_code`, `number`, `extension`, `formatted`, `e164`, `full_number`, `is_verified`, `metadata`, `created_at`/`updated_at` (ISO-8601).
- `PhoneNumbers\Http\Resources\PhoneNumberCollection` — collection of the above under a `data` key.
- Trait `PhoneNumbers\Concerns\WithPhoneNumbersResource` — adds protected `phoneNumbersResource(): array` returning `phone_numbers` and `primary_phone_number` keys (both `whenLoaded`, so eager-load the relations).

### Factory: `PhoneNumbers\Database\Factories\PhoneNumberFactory`

`PhoneNumber::factory()` — defaults: random type, `country_code` `'+1'`, 10-digit `number`, not primary/verified, and `extension` randomly present ~20% of the time (`faker->optional(0.2)`) — pin it explicitly in test assertions. States: `primary()`, `mobile()`, `home()`, `work()`, `fax()`, `verified()`, `withExtension()`. Set the morph yourself, e.g. `->for($customer, 'phoneable')`.

### Tenancy helper: `PhoneNumbers\Services\TenancyResolver`

`isMultiTenant(): bool` — resolves `tenancy_mode` (config override or auto-detection, cached per instance). Used by the install command; rarely needed directly.

## Canonical examples

### 1. Model setup + service-driven CRUD

Attach the trait, then use the service for writes (it maintains the single-primary invariant).

```php
use Illuminate\Database\Eloquent\Model;
use PhoneNumbers\Concerns\HasPhoneNumbers;
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;
use PhoneNumbers\DataTransferObjects\PhoneNumberDto;

class Customer extends Model
{
    use HasPhoneNumbers;
}

$service = app(PhoneNumberServiceInterface::class);
$customer = Customer::create(['name' => 'Acme Corp']);

$mobile = $service->store($customer, new PhoneNumberDto(
    type: 'mobile',
    countryCode: '+1:US',
    number: '5551234567',
    formatted: '(555) 123-4567',
    isPrimary: true,
));

$work = $service->store($customer, new PhoneNumberDto(
    type: 'work',
    countryCode: '+44:GB',
    number: '2071234567',
    extension: '456',
));

$mobile->e164;             // "+15551234567"
$mobile->dial_code;        // "+1"
$mobile->iso_country_code; // "US"
$mobile->full_number;      // "(555) 123-4567"
$work->full_number;        // "+442071234567 ext. 456"
```

### 2. Reading relationships and re-pointing primary

```php
$customer->phoneNumbers;                       // Collection of PhoneNumber
$customer->primaryPhoneNumber;                 // the is_primary row (or null)
$customer->phoneNumbersOfType('work')->get();  // filtered

// Promote another number; the old primary is unset automatically
$work->markAsPrimary();
$customer->fresh()->primaryPhoneNumber->number; // "2071234567"

use PhoneNumbers\Models\PhoneNumber;
PhoneNumber::forModel($customer)->verified()->get();
```

### 3. REST endpoints via the controller trait + route macro

```php
use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use PhoneNumbers\Concerns\ManagesPhoneNumbers;

class CustomerController extends Controller
{
    use AuthorizesRequests;
    use ManagesPhoneNumbers;

    protected function resolveParentModel(int $parentId): Model
    {
        return Customer::findOrFail($parentId);
    }
}

// routes/api.php
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::phoneNumberRoutes('customers', CustomerController::class);
});
```

`POST /customers/1/phone-numbers` with `{"country_code": "+1:US", "number": "5551234567", "is_primary": true}` returns 201 with the `PhoneNumberResource` payload. A `CustomerPolicy` with `view`/`update` must exist — `authorize()` denies otherwise.

### 4. Bulk sync from a parent form + embedded validation + resource output

```php
// Parent FormRequest
use Illuminate\Foundation\Http\FormRequest;
use PhoneNumbers\Http\Requests\PhoneNumberRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            ...PhoneNumberRequest::embeddedRules(), // keys under phone_numbers.*
        ];
    }
}

// In your update flow (controller/service):
use PhoneNumbers\Contracts\PhoneNumberServiceInterface;

app(PhoneNumberServiceInterface::class)->sync($customer, $request->input('phone_numbers', []));
// items WITH "id" → updated; WITHOUT "id" → created; rows missing from payload → deleted

// API resource
use Illuminate\Http\Resources\Json\JsonResource;
use PhoneNumbers\Concerns\WithPhoneNumbersResource;

class CustomerResource extends JsonResource
{
    use WithPhoneNumbersResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            ...$this->phoneNumbersResource(), // phone_numbers + primary_phone_number (whenLoaded)
        ];
    }
}

CustomerResource::make($customer->load('phoneNumbers', 'primaryPhoneNumber'));
```

## Events, exceptions & edge cases

- **No package events or custom exceptions.** Standard Eloquent model events fire for single-row create/update/delete — but `markAsPrimary()`, `unsetPrimaryForParent()`, and sync's delete-missing step use mass Eloquent-builder operations that **bypass model events/observers** on sibling rows (the mass updates still bump sibling `updated_at`, as Eloquent mass updates do).
- **Update is full-replace, not patch.** `PhoneNumberDto::toArray()` always emits every field, so `service->update()` with a DTO that omits `extension`/`formatted`/`metadata` nulls them, and omitting `is_primary`/`is_verified` resets them to `false`. Resend the complete record on update (the `sync` payload has the same semantics).
- **Sync never wipes everything.** With an empty `phone_numbers` array, `sync()` skips the delete step and returns the existing rows; `attachPhoneNumber()` likewise no-ops when the key is absent or empty. To delete all numbers, delete them explicitly.
- **404 semantics.** `updatePhoneNumber`/`deletePhoneNumber` abort 404 when the phone-number id exists but belongs to a different parent (`findForParent` scopes by morph type + id).
- **Authorization is mandatory in the controller trait** — `view` to list, `update` to store/update/delete. `Illuminate\Auth\Access\AuthorizationException` (403) when the policy denies or no policy is registered.
- **No phone validation library.** `number` is any string ≤ 20 chars; `e164` is naive concatenation (`"+", dial code, number`). Normalize/validate digits in your app if you need real E.164 guarantees. Garbage in (`number: "abc"`) produces `"+1abc"`.
- **`is_verified` is client-settable** through `PhoneNumberRequest` — strip it from request data if verification must be server-controlled.
- **Hard deletes only**; no SoftDeletes on `PhoneNumber`. No queue or cache interaction anywhere in the package.
- **Multi-tenancy:** auto-loaded package migrations run on the central connection too; in stancl/tenancy apps run `phone-numbers:install` (publishes to `database/migrations/tenant/`) and be aware the table will also exist centrally unless you skip the auto-loaded migration.
- **`exists:phone_numbers,id`** in `embeddedRules()` hits the default DB connection — in tenant contexts ensure validation runs on the tenant connection.

## Common mistakes

- ❌ Creating primaries with `PhoneNumber::create([... 'is_primary' => true])` or `$customer->phoneNumbers()->create()` → ✅ use `PhoneNumberServiceInterface::store()` (or `markAsPrimary()`), otherwise multiple rows end up `is_primary = true` and `primaryPhoneNumber` (a `MorphOne`) returns an arbitrary one.
- ❌ Treating `service->update()` / sync items as a partial PATCH (sending only the changed field) → ✅ send the full record; omitted fields are reset to `null`/`false` (including `is_primary`).
- ❌ `PhoneNumberDto::fromArray(['number' => '5551234567'])` without `country_code` → ✅ both `country_code` and `number` keys are required by `fromArray()`; only `type`, flags, and nullable fields have defaults.
- ❌ Storing `country_code` as `'US'` or `'1'` and expecting `e164`/`iso_country_code` to work → ✅ store `'+1:US'` (compound) or `'+1'` (plain); `iso_country_code` is only non-null for the compound `dial:ISO` format.
- ❌ Using `ManagesPhoneNumbers` on a controller with no `$service->getById()` and no override → ✅ override `resolveParentModel(int $parentId): Model` (e.g. `findOrFail`) or expose a `$service` with `getById()`; also add `AuthorizesRequests` and a policy or every request 403s.
- ❌ Expecting `phone_numbers` / `primary_phone_number` in `WithPhoneNumbersResource` output after `Customer::find()` → ✅ both use `whenLoaded()`; eager-load `->load('phoneNumbers', 'primaryPhoneNumber')` first.
- ❌ Calling `sync($parent, [])` to remove all numbers → ✅ empty payloads are a no-op for deletion; delete rows explicitly via `service->delete()`.
- ❌ Setting `allow_custom_types => false` and still sending `type: 'emergency'` → ✅ with strict mode, `type` must be one of `config('phone-numbers.types')`; either add it to `types` or keep `allow_custom_types` true.

## Version notes

- Documented against the current release of `joepages/laravel-phone-numbers` on Packagist. Requires PHP ^8.2, Laravel (illuminate/*) ^11.0 or ^12.0.
- Plain dial codes (`country_code: '+1'`) are the legacy format — still fully supported, but `iso_country_code` returns `null`; prefer compound `'+1:US'`.
- No deprecations or version-gated APIs at this time.
