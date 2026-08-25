<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('component', 80);
            $table->string('scope_type', 40)->default('VEHICLE');
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
            $table->string('manufacturer', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedInteger('interval_days')->nullable();
            $table->decimal('interval_hours', 10, 1)->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'component', 'status']);
            $table->index(['vehicle_id', 'status']);
        });

        Schema::create('tachograph_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('number', 80);
            $table->string('manufacturer', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->date('installed_at')->nullable();
            $table->date('calibrated_at')->nullable();
            $table->string('certificate_number', 120)->nullable();
            $table->date('expires_at');
            $table->date('last_maintenance_at')->nullable();
            $table->json('history')->default('[]');
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['company_id', 'number']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index(['vehicle_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tachograph_records');
        Schema::dropIfExists('maintenance_plans');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
