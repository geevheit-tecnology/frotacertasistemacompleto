<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('code', 40)->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
        });

        Schema::create('fuel_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('fuel_type', 40);
            $table->decimal('quantity_liters', 12, 3);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('total_amount', 12, 2);
            $table->unsignedInteger('odometer_km');
            $table->decimal('average_km_per_liter', 8, 2)->nullable();
            $table->string('invoice_number', 80)->nullable();
            $table->timestampTz('filled_at');
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'filled_at']);
            $table->index(['vehicle_id', 'filled_at']);
        });

        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignUuid('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('type', 20);
            $table->string('category', 80);
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('due_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('status', 32)->default('OPEN');
            $table->string('origin', 120)->default('manual');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'due_at']);
            $table->index(['vehicle_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('fuel_records');
        Schema::dropIfExists('cost_centers');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
