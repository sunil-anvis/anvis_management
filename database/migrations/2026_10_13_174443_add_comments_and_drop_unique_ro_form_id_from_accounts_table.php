<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bypass strict date check for legacy '0000-00-00' values during ALTER TABLE
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('accounts', function (Blueprint $table) {
            $table->text('comments')->nullable()->after('executive');
            $table->dropForeign(['ro_form_id']);
            $table->dropUnique('accounts_ro_form_id_unique');
            $table->foreign('ro_form_id')->references('id')->on('ro_forms')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("SET SESSION sql_mode = ''");

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('comments');
            $table->dropForeign(['ro_form_id']);
            $table->unique('ro_form_id');
            $table->foreign('ro_form_id')->references('id')->on('ro_forms')->cascadeOnDelete();
        });
    }
};
