<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('postcode');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('place_id')->nullable()->after('longitude');
            $table->string('landmark')->nullable()->after('place_id');
            $table->text('delivery_instructions')->nullable()->after('landmark');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'place_id', 'landmark', 'delivery_instructions']);
        });
    }
};
