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
        Schema::create('ro_forms', function (Blueprint $table) {
            $table->id();
            $table->string('ro_number')->unique();

            $table->foreignId('departments_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('vendor_name');
            $table->string('vendor_address')->nullable();
            $table->string('vendor_gst_no')->nullable();
            $table->string('vendor_contact')->nullable();
            $table->string('vendor_email')->nullable();

            $table->string('client_name');
            $table->string('service');
            $table->string('ad_type');
            $table->string('ad_unit');
            $table->string('buy_type');
            $table->string('deliverables');
            $table->decimal('volume', 12, 2);
            $table->decimal('bid', 12, 2);
            $table->date('completion_date');
            $table->decimal('buying_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->string('status')->default('pending');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('primary_lead_id')->nullable()->constrained('users');
            $table->foreignId('secondary_lead_id')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ro_forms');
    }
};
