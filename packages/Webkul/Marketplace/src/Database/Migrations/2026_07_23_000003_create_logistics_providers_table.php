<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            // internal: our own marketplace delivery agents.
            // vendor_managed: the seller delivers with their own staff/vehicle.
            // third_party: an external logistics company with a real API integration.
            $table->enum('type', ['internal', 'vendor_managed', 'third_party'])->default('internal');
            $table->string('adapter_class');
            $table->boolean('is_active')->default(false);
            // Credentials/config the adapter needs (API base URL, account ID, etc).
            // Actual secrets are stored via encrypted core_config, this only
            // holds non-secret adapter configuration (e.g. webhook path, region).
            $table->json('config')->nullable();
            $table->decimal('base_fee', 12, 2)->default(0);
            $table->decimal('fee_per_km', 12, 2)->default(0);
            $table->unsignedInteger('max_distance_km')->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_providers');
    }
};
