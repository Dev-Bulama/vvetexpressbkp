<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_provider_id')->constrained('logistics_providers')->cascadeOnDelete();
            $table->string('event_type');
            // The provider's own event/idempotency identifier. Unique per
            // provider so a redelivered webhook never double-processes.
            $table->string('external_event_id');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_error')->nullable();
            $table->timestamps();

            $table->unique(['logistics_provider_id', 'external_event_id'], 'logistics_webhook_provider_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_webhook_events');
    }
};
