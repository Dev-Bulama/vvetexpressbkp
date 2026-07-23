<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('marketplace_sellers')->cascadeOnDelete();
            $table->unsignedInteger('customer_id')->nullable();
            $table->foreignId('logistics_provider_id')->constrained('logistics_providers')->restrictOnDelete();
            $table->foreignId('logistics_service_type_id')->constrained('logistics_service_types')->restrictOnDelete();
            $table->foreignId('delivery_agent_id')->nullable()->constrained('delivery_agents')->nullOnDelete();

            $table->string('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->string('dropoff_address');
            $table->decimal('dropoff_latitude', 10, 8);
            $table->decimal('dropoff_longitude', 11, 8);

            $table->enum('status', [
                'requested',
                'searching_agent',
                'agent_assigned',
                'en_route_to_vendor',
                'arrived_at_vendor',
                'picked_up',
                'en_route_to_customer',
                'arrived_at_customer',
                'completed',
                'failed',
                'cancelled',
            ])->default('requested');

            $table->decimal('distance_km', 8, 2)->nullable();
            $table->unsignedInteger('duration_minutes_estimate')->nullable();
            $table->unsignedInteger('fee_minor');
            $table->char('currency_code', 3);

            $table->string('pickup_verification_code', 6)->nullable();
            $table->string('dropoff_verification_code', 6)->nullable();
            $table->timestamp('picked_up_verified_at')->nullable();
            $table->timestamp('delivered_verified_at')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('agent_assigned_at')->nullable();
            $table->timestamp('arrived_at_vendor_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('arrived_at_customer_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['order_id', 'seller_id']);
            $table->index(['delivery_agent_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
