<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_service_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('engine', 120)->nullable();
            $table->string('oil_type', 80)->nullable();
            $table->string('viscosity', 40)->nullable();
            $table->string('manufacturer', 120)->nullable();
            $table->unsignedInteger('service_odometer_km');
            $table->date('service_date');
            $table->unsignedInteger('interval_km');
            $table->unsignedInteger('next_service_odometer_km');
            $table->string('supplier', 160)->nullable();
            $table->string('invoice_number', 80)->nullable();
            $table->decimal('cost_amount', 12, 2)->nullable();
            $table->string('work_order_number', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id']);
            $table->index(['vehicle_id', 'service_date']);
            $table->index('next_service_odometer_km');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_service_records');
    }
};
