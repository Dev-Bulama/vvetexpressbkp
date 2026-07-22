<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_agent_vehicles', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bicycle', 'motorcycle', 'car', 'van'])->default('motorcycle');
            $table->string('plate_number')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_agent_vehicles');
    }
};
