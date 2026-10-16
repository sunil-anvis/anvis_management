<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ro_forms', function (Blueprint $table) {
            // 1. Drop unique constraint on ro_number to allow reusing cancelled RO numbers
            $table->dropUnique('ro_forms_ro_number_unique');
            $table->index('ro_number');

            // 2. Drop foreign key on approved_by so it can store approval count (0, 1, 2)
            $table->dropForeign('ro_forms_approved_by_foreign');
        });

        Schema::table('ro_forms', function (Blueprint $table) {
            // 3. Change approved_by to tinyInteger count (0: no one, 1: one approved, 2: both approved)
            $table->tinyInteger('approved_by')->default(0)->change();

            // 4. Add approved_leads JSON column to track user IDs of managers who approved
            $table->json('approved_leads')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ro_forms', function (Blueprint $table) {
            $table->dropColumn('approved_leads');
            $table->foreignId('approved_by')->nullable()->change();
            $table->foreign('approved_by')->references('id')->on('users');
            $table->dropIndex(['ro_number']);
            $table->unique('ro_number', 'ro_forms_ro_number_unique');
        });
    }
};
