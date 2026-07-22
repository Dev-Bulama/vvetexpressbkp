<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bounded location history, only recorded while a delivery is active
     * (see LocationUpdateService) - this is not a full-time tracking log,
     * it exists to redraw the route/ETA and for dispute review.
     */
    public function up(): void
    {
        Schema::create('delivery_agent_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
            $table->foreignId('delivery_agent_id')->constrained('delivery_agents')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('heading_degrees', 5, 1)->nullable();
            $table->decimal('speed_kph', 6, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['delivery_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_agent_locations');
    }
};
