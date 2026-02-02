# PhoneNumbers Package

Polymorphic phone numbers package for Laravel. Attach N phone numbers to any Eloquent model.

## Installation

```bash
composer require your-vendor/phone-numbers
php artisan phone-numbers:install
php artisan migrate
```

## Usage

### Add the trait to your model

```php
use PhoneNumbers\Concerns\HasPhoneNumbers;

class Facility extends Model
{
    use HasPhoneNumbers;
}
```

### Register routes in your route file

```php
use App\Http\Controllers\Api\FacilityController;

Route::phoneNumberRoutes('facilities', FacilityController::class);
```

### Add the controller trait

```php
use PhoneNumbers\Concerns\ManagesPhoneNumbers;

class FacilityController extends BaseApiController
{
    use ManagesPhoneNumbers;
}
```

### Add phone numbers to your API resource

```php
use PhoneNumbers\Concerns\WithPhoneNumbersResource;

class FacilityResource extends BaseResource
{
    use WithPhoneNumbersResource;

    public function toArray($request): array
    {
        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
        ], $this->phoneNumbersResource());
    }
}
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=phone-numbers-config
```

## Testing

```bash
composer test
```
