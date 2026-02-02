<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->string('phoneable_type');
            $table->unsignedBigInteger('phoneable_id');

            // Phone number classification
            $table->string('type', 50)->default('mobile');
            $table->boolean('is_primary')->default(false);

            // Phone number fields
            $table->string('country_code', 5);
            $table->string('number');
            $table->string('extension')->nullable();
            $table->string('formatted')->nullable();

            // Verification
            $table->boolean('is_verified')->default(false);

            // Extensibility
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['phoneable_type', 'phoneable_id'], 'phone_numbers_phoneable_index');
            $table->index('type');
            $table->index('is_primary');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
