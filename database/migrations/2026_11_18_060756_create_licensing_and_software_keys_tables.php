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
        // 1. Software / Client Licenses
        if (!Schema::hasTable('software_licenses')) {
            Schema::create('software_licenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->string('license_key')->unique();
                $table->string('software_name'); // e.g. Falcon ERP Pro, Tally Prime, AutoCAD, Windows Server
                $table->string('license_type')->default('perpetual'); // subscription, perpetual, trial, seat_based
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->integer('seats_total')->default(1);
                $table->integer('seats_allocated')->default(0);
                $table->date('issued_date');
                $table->date('expiry_date')->nullable();
                $table->string('status')->default('active'); // active, expired, suspended, pending_renewal
                $table->decimal('cost', 15, 2)->default(0);
                $table->string('vendor')->nullable(); // e.g. Microsoft, Autodesk, Internal Falcon
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['expiry_date']);
            });
        }

        // 2. License Allocation Logs / Device Binding
        if (!Schema::hasTable('license_allocations')) {
            Schema::create('license_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('license_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('assigned_name');
                $table->string('department')->nullable();
                $table->string('device_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->date('assigned_date');
                $table->string('status')->default('active'); // active, revoked
                $table->timestamps();

                $table->foreign('license_id')->references('id')->on('software_licenses')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_allocations');
        Schema::dropIfExists('software_licenses');
    }
};
