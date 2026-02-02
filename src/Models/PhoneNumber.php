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

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'metadata' => 'array',
        ];
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
     * Get the E.164 formatted phone number (+{country_code}{number}).
     */
    public function getE164Attribute(): string
    {
        $code = ltrim($this->country_code, '+');

        return "+{$code}{$this->number}";
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

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \PhoneNumbers\Database\Factories\PhoneNumberFactory::new();
    }
}
