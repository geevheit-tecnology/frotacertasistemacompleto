<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('number', 80);
            $table->string('type', 80);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 32)->default('ACTIVE');
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'ends_at']);
        });

        Schema::create('insurance_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('policy_number', 100);
            $table->string('provider', 160);
            $table->date('starts_at')->nullable();
            $table->date('expires_at');
            $table->decimal('premium_amount', 12, 2)->default(0);
            $table->string('status', 32)->default('ACTIVE');
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'expires_at']);
        });

        Schema::create('fines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('notice_number', 120);
            $table->date('occurred_at');
            $table->date('due_at')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 32)->default('OPEN');
            $table->text('description')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'due_at']);
        });

        Schema::create('trips', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('origin', 160);
            $table->string('destination', 160);
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->string('status', 32)->default('SCHEDULED');
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'vehicle_id', 'starts_at']);
        });

        Schema::create('telematics_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->timestampTz('recorded_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('odometer_km')->nullable();
            $table->decimal('hourmeter', 10, 2)->nullable();
            $table->decimal('speed_kmh', 8, 2)->nullable();
            $table->string('source', 80)->default('API');
            $table->json('payload')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'vehicle_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telematics_events');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('fines');
        Schema::dropIfExists('insurance_policies');
        Schema::dropIfExists('contracts');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
