<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->boolean('is_hidden_from_public')->default(false)->after('item_code');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->dropColumn('is_hidden_from_public');
        });
    }
};
