<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tires', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('fire_number', 80);
            $table->string('manufacturer', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('size', 40)->nullable();
            $table->string('dot', 40)->nullable();
            $table->date('purchased_at')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->unsignedInteger('initial_km')->nullable();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('position', 40)->nullable();
            $table->decimal('tread_depth_mm', 5, 2)->nullable();
            $table->decimal('pressure_psi', 6, 2)->nullable();
            $table->unsignedInteger('life_cycle')->default(1);
            $table->unsignedInteger('retread_count')->default(0);
            $table->string('status', 32)->default('ESTOQUE');
            $table->timestamps();
            $table->unique(['company_id', 'fire_number']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index(['vehicle_id', 'position']);
        });

        Schema::create('tire_inspections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('tire_id')->constrained('tires')->cascadeOnDelete();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('position', 40)->nullable();
            $table->decimal('tread_depth_mm', 5, 2);
            $table->decimal('pressure_psi', 6, 2)->nullable();
            $table->string('wear_pattern', 80)->nullable();
            $table->text('notes')->nullable();
            $table->json('photo_paths')->default('[]');
            $table->decimal('visual_confidence', 5, 2)->nullable();
            $table->string('visual_status', 40)->nullable();
            $table->timestampTz('inspected_at');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'inspected_at']);
            $table->index(['tire_id', 'inspected_at']);
        });

        Schema::create('tire_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('tire_id')->constrained('tires')->cascadeOnDelete();
            $table->foreignUuid('from_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignUuid('to_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('from_position', 40)->nullable();
            $table->string('to_position', 40)->nullable();
            $table->string('type', 40);
            $table->text('reason')->nullable();
            $table->timestampTz('moved_at');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'type']);
            $table->index(['tire_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tire_movements');
        Schema::dropIfExists('tire_inspections');
        Schema::dropIfExists('tires');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
