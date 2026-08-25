<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('vehicle_type', 80)->nullable();
            $table->json('items')->default('[]');
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
        });

        Schema::create('checklist_executions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('template_id')->nullable()->constrained('checklist_templates')->nullOnDelete();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->unsignedInteger('odometer_km');
            $table->decimal('hourmeter', 10, 1)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestampTz('performed_at');
            $table->string('status', 32)->default('COMPLETED');
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'performed_at']);
            $table->index(['vehicle_id', 'performed_at']);
        });

        Schema::create('checklist_responses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('checklist_execution_id')->constrained('checklist_executions')->cascadeOnDelete();
            $table->string('item_key', 120);
            $table->string('label', 255);
            $table->string('answer', 80);
            $table->string('severity', 20)->default('INFO');
            $table->text('notes')->nullable();
            $table->json('evidence_paths')->default('[]');
            $table->timestamps();
            $table->index(['checklist_execution_id', 'severity']);
        });

        Schema::create('non_conformities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('checklist_execution_id')->constrained('checklist_executions')->cascadeOnDelete();
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('item_key', 120);
            $table->string('description', 500);
            $table->string('severity', 20);
            $table->string('status', 32)->default('OPEN');
            $table->json('evidence_paths')->default('[]');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'severity']);
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_conformities');
        Schema::dropIfExists('checklist_responses');
        Schema::dropIfExists('checklist_executions');
        Schema::dropIfExists('checklist_templates');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
