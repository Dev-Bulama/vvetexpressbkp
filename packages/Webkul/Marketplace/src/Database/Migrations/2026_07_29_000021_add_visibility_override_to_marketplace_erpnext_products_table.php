<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the plain is_hidden_from_public boolean with a genuine 3-state
 * override, needed now that the sync can auto-hide a product for being
 * incomplete (no image, or no price): "no override" (let the sync decide
 * based on completeness), "hidden" (an admin deliberately hid it, always
 * wins), or "visible" (an admin deliberately forced an otherwise-incomplete
 * item public, and that decision must also always win - a boolean can't
 * tell "admin explicitly overrode this" apart from "nobody has touched
 * this yet").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->string('visibility_override')->nullable()->after('is_hidden_from_public');
        });

        DB::table('marketplace_erpnext_products')
            ->where('is_hidden_from_public', true)
            ->update(['visibility_override' => 'hidden']);

        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->dropColumn('is_hidden_from_public');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->boolean('is_hidden_from_public')->default(false)->after('item_code');
        });

        // Round-trips 'hidden' correctly; a 'visible' override (admin forced
        // an incomplete item public) has no boolean equivalent and is lost
        // on rollback, same as "no override" - both become false.
        DB::table('marketplace_erpnext_products')
            ->where('visibility_override', 'hidden')
            ->update(['is_hidden_from_public' => true]);

        Schema::table('marketplace_erpnext_products', function (Blueprint $table) {
            $table->dropColumn('visibility_override');
        });
    }
};
