<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_service_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_provider_id')->constrained('logistics_providers')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->enum('vehicle_type', ['bicycle', 'motorcycle', 'car', 'van', 'none'])->default('motorcycle');
            $table->text('description')->nullable();
            $table->boolean('tracking_available')->default(true);
            $table->unsignedInteger('estimated_pickup_minutes')->default(15);
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('fee_per_km', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['logistics_provider_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_service_types');
    }
};
