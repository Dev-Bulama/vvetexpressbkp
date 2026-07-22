<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('quote_token')->unique();
            $table->unsignedInteger('cart_id')->nullable();
            $table->foreignId('seller_id')->nullable()->constrained('marketplace_sellers')->nullOnDelete();
            $table->foreignId('logistics_provider_id')->constrained('logistics_providers')->cascadeOnDelete();
            $table->foreignId('logistics_service_type_id')->constrained('logistics_service_types')->cascadeOnDelete();
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->decimal('dropoff_latitude', 10, 8);
            $table->decimal('dropoff_longitude', 11, 8);
            $table->decimal('distance_km', 8, 2);
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('fee_minor');
            $table->char('currency_code', 3);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['cart_id', 'seller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_quotes');
    }
};
