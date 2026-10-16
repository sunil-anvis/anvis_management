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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ro_form_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('bill_name');
            $table->string('anvis_invoice');
            $table->string('anvis_invoice_date');
            $table->string('anvis_status');
            $table->string('vendor_invoice');
            $table->string('vendor_invoice_date');
            $table->string('vendor_status');
            $table->decimal('external_amount',12, 2);
            $table->string('executive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
