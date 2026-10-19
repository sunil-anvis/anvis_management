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
        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropForeign(['permission_id']);
            $table->dropForeign(['role_id']);
            $table->dropPrimary(['permission_id', 'role_id']);
        });

        DB::statement('ALTER TABLE `permission_role` MODIFY COLUMN `role_id` BIGINT UNSIGNED NOT NULL FIRST');
        DB::statement('ALTER TABLE `permission_role` MODIFY COLUMN `permission_id` BIGINT UNSIGNED NOT NULL AFTER `role_id`');

        Schema::table('permission_role', function (Blueprint $table) {
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['permission_id']);
            $table->dropPrimary(['role_id', 'permission_id']);
        });

        DB::statement('ALTER TABLE `permission_role` MODIFY COLUMN `permission_id` BIGINT UNSIGNED NOT NULL FIRST');
        DB::statement('ALTER TABLE `permission_role` MODIFY COLUMN `role_id` BIGINT UNSIGNED NOT NULL AFTER `permission_id`');

        Schema::table('permission_role', function (Blueprint $table) {
            $table->primary(['permission_id', 'role_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }
};
