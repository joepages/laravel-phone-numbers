<?php

declare(strict_types=1);

namespace PhoneNumbers\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PhoneNumber extends Model
{
    use HasFactory;

    protected $table = 'phone_numbers';

    protected $fillable = [
        'phoneable_type',
        'phoneable_id',
        'type',
        'is_primary',
        'country_code',
        'number',
        'extension',
        'formatted',
        'is_verified',
        'metadata',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \PhoneNumbers\Database\Factories\PhoneNumberFactory::new();
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function phoneable(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('phoneable_type', $model->getMorphClass())
            ->where('phoneable_id', $model->getKey());
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Mark this phone number as primary and unmark all other phone numbers for the same parent.
     */
    public function markAsPrimary(): bool
    {
        static::where('phoneable_type', $this->phoneable_type)
            ->where('phoneable_id', $this->phoneable_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->is_primary = true;

        return $this->save();
    }

    /**
     * Get the E.164 formatted phone number (+{dialCode}{number}).
     *
     * Supports both plain dial codes ("+1") and compound codes ("+1:US").
     * When a compound code is stored, the dial code portion (before the colon)
     * is used for the E.164 representation.
     */
    public function getE164Attribute(): string
    {
        $code = $this->country_code;

        // Handle compound format "+1:US" — extract the dial code before the colon
        if (str_contains($code, ':')) {
            $code = explode(':', $code)[0];
        }

        $code = ltrim($code, '+');

        return "+{$code}{$this->number}";
    }

    /**
     * Get the ISO country code portion from a compound country_code (e.g. "US" from "+1:US").
     * Returns null if the country_code is not in compound format.
     */
    public function getIsoCountryCodeAttribute(): ?string
    {
        if (str_contains($this->country_code, ':')) {
            return explode(':', $this->country_code)[1] ?? null;
        }

        return null;
    }

    /**
     * Get the dial code portion from the country_code (e.g. "+1" from "+1:US").
     * If stored in plain format, returns the country_code as-is.
     */
    public function getDialCodeAttribute(): string
    {
        if (str_contains($this->country_code, ':')) {
            return explode(':', $this->country_code)[0];
        }

        return $this->country_code;
    }

    /**
     * Get the full number: formatted or E.164 + extension.
     */
    public function getFullNumberAttribute(): string
    {
        $base = $this->formatted ?: $this->e164;

        if ($this->extension) {
            return "{$base} ext. {$this->extension}";
        }

        return $base;
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
