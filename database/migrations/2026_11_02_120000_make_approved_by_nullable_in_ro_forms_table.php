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
        Schema::table('ro_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->default(null)->change();
        });

        // Convert any existing 0 values to null
        DB::table('ro_forms')->where('approved_by', 0)->update(['approved_by' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ro_forms', function (Blueprint $table) {
            $table->tinyInteger('approved_by')->default(0)->change();
        });
    }
};
