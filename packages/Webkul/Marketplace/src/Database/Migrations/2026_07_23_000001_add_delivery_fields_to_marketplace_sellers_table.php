<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->unsignedInteger('service_radius_km')->default(15)->after('longitude');
            $table->time('opening_time')->nullable()->after('service_radius_km');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->boolean('is_delivery_enabled')->default(true)->after('closing_time');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->dropColumn(['service_radius_km', 'opening_time', 'closing_time', 'is_delivery_enabled']);
        });
    }
};
