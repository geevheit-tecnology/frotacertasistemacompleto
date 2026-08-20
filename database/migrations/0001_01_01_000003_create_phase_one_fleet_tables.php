<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('plate', 16);
            $table->string('fleet_number', 40)->nullable();
            $table->string('manufacturer', 120)->nullable();
            $table->string('model', 120)->nullable();
            $table->smallInteger('model_year')->nullable();
            $table->unsignedInteger('current_odometer_km')->default(0);
            $table->unsignedInteger('current_hourmeter')->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['company_id', 'plate']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
        });

        Schema::create('drivers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('document_number', 32)->nullable();
            $table->string('license_number', 60)->nullable();
            $table->string('license_category', 12)->nullable();
            $table->date('license_expires_at')->nullable();
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index('license_expires_at');
        });

        Schema::create('vehicle_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 32)->default('VALID');
            $table->foreignUuid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('file_path')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'expires_at']);
        });

        Schema::create('driver_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status', 32)->default('VALID');
            $table->foreignUuid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('file_path')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
