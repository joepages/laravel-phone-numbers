# Laravel Phone Numbers

[![Tests](https://github.com/joepages/laravel-phone-numbers/actions/workflows/tests.yml/badge.svg)](https://github.com/joepages/laravel-phone-numbers/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/joepages/laravel-phone-numbers.svg)](https://packagist.org/packages/joepages/laravel-phone-numbers)
[![License](https://img.shields.io/packagist/l/joepages/laravel-phone-numbers.svg)](https://packagist.org/packages/joepages/laravel-phone-numbers)

Polymorphic phone numbers for Laravel. Attach multiple phone numbers to any Eloquent model with full CRUD, bulk sync, primary management, E.164 formatting, and multi-tenancy awareness.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require joepages/laravel-phone-numbers
```

Run the install command to publish the config and migrations:

```bash
php artisan phone-numbers:install
php artisan migrate
```

The installer auto-detects [stancl/tenancy](https://tenancyforlaravel.com/) and publishes migrations to `database/migrations/tenant/` when present.

### Install options

```bash
php artisan phone-numbers:install --force            # Overwrite existing files
php artisan phone-numbers:install --skip-migrations  # Only publish config
```

## Quick Start

### 1. Add the trait to your model

```php
use PhoneNumbers\Concerns\HasPhoneNumbers;

class Facility extends Model
{
    use HasPhoneNumbers;
}
```

### 2. Add the controller trait

```php
use PhoneNumbers\Concerns\ManagesPhoneNumbers;

class FacilityController extends BaseApiController
{
    use ManagesPhoneNumbers;
}
```

### 3. Register routes

```php
Route::phoneNumberRoutes('facilities', FacilityController::class);
```

This registers the following routes:

| Method | URI | Action |
|--------|-----|--------|
| GET | `/facilities/{facility}/phone-numbers` | `listPhoneNumbers` |
| POST | `/facilities/{facility}/phone-numbers` | `storePhoneNumber` |
| PUT | `/facilities/{facility}/phone-numbers/{phoneNumber}` | `updatePhoneNumber` |
| DELETE | `/facilities/{facility}/phone-numbers/{phoneNumber}` | `deletePhoneNumber` |

## Model Trait API

The `HasPhoneNumbers` trait provides three relationships on your model:

```php
$facility->phoneNumbers;                 // All phone numbers (MorphMany)
$facility->primaryPhoneNumber;           // Primary phone number (MorphOne)
$facility->phoneNumbersOfType('mobile'); // Filtered by type (MorphMany)
```

## PhoneNumber Model

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `type` | string | Phone type (`mobile`, `home`, `work`, `fax`, `other`) |
| `is_primary` | boolean | Whether this is the primary phone number |
| `country_code` | string | Dial code (e.g. `+1`, `+44`) |
| `number` | string | Phone number digits |
| `extension` | string\|null | Extension number |
| `formatted` | string\|null | Display-formatted number (e.g. `(555) 123-4567`) |
| `is_verified` | boolean | Whether the number has been verified |
| `metadata` | array\|null | Custom JSON data |

### Scopes

```php
PhoneNumber::primary()->get();           // Only primary numbers
PhoneNumber::ofType('mobile')->get();    // Filter by type
PhoneNumber::forModel($facility)->get(); // All numbers for a specific model
PhoneNumber::verified()->get();          // Only verified numbers
```

### Helpers

```php
$phone->markAsPrimary();   // Sets as primary, unsets all others for the same parent
$phone->e164;              // "+15551234567" (E.164 format)
$phone->full_number;       // "(555) 123-4567 ext. 200" (formatted + extension)
```

## Controller Trait

The `ManagesPhoneNumbers` trait provides two integration modes:

### Standalone CRUD

Use the `storePhoneNumber`, `updatePhoneNumber`, `deletePhoneNumber`, and `listPhoneNumbers` methods directly via the route macro.

### Bulk Sync via BaseApiController

When your controller extends `BaseApiController`, the `attachPhoneNumber()` method is called automatically during `store()` and `update()`. Send a `phone_numbers` array in the request body:

```json
{
  "name": "Main Facility",
  "phone_numbers": [
    {
      "id": 1,
      "country_code": "+1",
      "number": "5559999999",
      "formatted": "(555) 999-9999"
    },
    {
      "country_code": "+1",
      "number": "5551234567",
      "type": "work",
      "is_primary": true
    }
  ]
}
```

- Records **with an `id`** are updated
- Records **without an `id`** are created
- Existing records **not included** in the array are deleted

## API Resource

Add phone numbers to your JSON responses:

```php
use PhoneNumbers\Concerns\WithPhoneNumbersResource;

class FacilityResource extends JsonResource
{
    use WithPhoneNumbersResource;

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            ...$this->phoneNumbersResource(),
        ];
    }
}
```

## Validation

The `PhoneNumberRequest` form request validates:

| Field | Rules |
|-------|-------|
| `country_code` | required, string, max:5 |
| `number` | required, string, max:20 |
| `extension` | nullable, string, max:10 |
| `formatted` | nullable, string, max:30 |
| `type` | sometimes, string (validated against config when `allow_custom_types` is false) |
| `is_primary` | sometimes, boolean |
| `is_verified` | sometimes, boolean |
| `metadata` | nullable, array |

## Configuration

```php
// config/phone-numbers.php

return [
    // 'auto' detects stancl/tenancy, 'single' or 'multi' to force
    'tenancy_mode' => 'auto',

    // Allowed phone number types
    'types' => ['mobile', 'home', 'work', 'fax', 'other'],

    // Default type when none specified
    'default_type' => 'mobile',

    // When false, only types in the 'types' array are accepted
    'allow_custom_types' => true,
];
```

## Database Schema

```sql
CREATE TABLE phone_numbers (
    id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    phoneable_type VARCHAR(255) NOT NULL,
    phoneable_id   BIGINT UNSIGNED NOT NULL,
    type           VARCHAR(50) DEFAULT 'mobile',
    is_primary     BOOLEAN DEFAULT FALSE,
    country_code   VARCHAR(5) NOT NULL,
    number         VARCHAR(255) NOT NULL,
    extension      VARCHAR(255) NULL,
    formatted      VARCHAR(255) NULL,
    is_verified    BOOLEAN DEFAULT FALSE,
    metadata       JSON NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,

    INDEX (phoneable_type, phoneable_id),
    INDEX (type),
    INDEX (is_primary),
    INDEX (number)
);
```

## Testing

```bash
composer test
```

## License

MIT License. See [LICENSE](LICENSE) for details.
