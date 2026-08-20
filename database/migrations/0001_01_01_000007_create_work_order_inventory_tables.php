<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('document_number', 32)->nullable();
            $table->string('type', 40)->default('WORKSHOP');
            $table->string('phone', 40)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'type', 'status']);
        });

        Schema::create('inventory_parts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('sku', 80)->nullable();
            $table->string('name', 160);
            $table->string('unit', 20)->default('UN');
            $table->decimal('quantity_on_hand', 12, 3)->default(0);
            $table->decimal('minimum_quantity', 12, 3)->default(0);
            $table->decimal('average_cost', 12, 2)->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['company_id', 'sku']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
        });

        Schema::create('work_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('number', 80);
            $table->string('type', 40)->default('PREVENTIVE');
            $table->string('status', 32)->default('OPEN');
            $table->string('priority', 32)->default('MEDIUM');
            $table->text('description');
            $table->date('opened_at');
            $table->date('scheduled_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->unsignedInteger('odometer_km')->nullable();
            $table->decimal('labor_cost', 12, 2)->nullable();
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
        });

        Schema::create('work_order_parts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignUuid('inventory_part_id')->constrained('inventory_parts')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('inventory_part_id')->constrained('inventory_parts')->cascadeOnDelete();
            $table->foreignUuid('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->string('type', 32);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('work_order_parts');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('inventory_parts');
        Schema::dropIfExists('suppliers');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
