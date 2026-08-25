<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('name', 160);
            $table->string('trigger_event', 160);
            $table->json('conditions')->default('{}');
            $table->json('actions')->default('[]');
            $table->string('severity', 20)->default('INFO');
            $table->integer('priority')->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->string('status', 32)->default('ACTIVE');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'trigger_event', 'status']);
        });

        Schema::create('rule_executions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('rule_id')->constrained('rules')->cascadeOnDelete();
            $table->string('trigger_event', 160);
            $table->string('entity', 120);
            $table->uuid('entity_id');
            $table->json('input_payload')->default('{}');
            $table->json('result_payload')->default('{}');
            $table->string('status', 32)->default('MATCHED');
            $table->string('idempotency_key', 128);
            $table->timestampTz('executed_at')->useCurrent();
            $table->timestamps();
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['group_id', 'company_id', 'branch_id', 'trigger_event', 'status']);
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('alert_id')->nullable()->constrained('alerts')->nullOnDelete();
            $table->foreignUuid('rule_execution_id')->nullable()->constrained('rule_executions')->nullOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('entity', 120)->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('priority', 32)->default('MEDIUM');
            $table->date('due_at')->nullable();
            $table->string('origin', 120);
            $table->string('status', 32)->default('OPEN');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status', 'due_at']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 40)->default('INTERNAL');
            $table->string('title', 180);
            $table->text('message');
            $table->json('payload')->default('{}');
            $table->string('status', 32)->default('PENDING');
            $table->timestampTz('sent_at')->nullable();
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'channel', 'status']);
        });

        Schema::create('evidences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('entity', 120);
            $table->uuid('entity_id');
            $table->string('type', 40);
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('storage_path', 500);
            $table->json('metadata')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('rule_executions');
        Schema::dropIfExists('rules');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
