<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_erpnext_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id')->nullable();
            $table->string('external_id')->unique();
            $table->string('external_parent_id')->nullable();
            $table->string('source')->default('erpnext');
            $table->string('sync_status')->default('synced');
            $table->string('external_payload_hash')->nullable();
            $table->boolean('is_disabled_locally')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_erpnext_categories');
    }
};
