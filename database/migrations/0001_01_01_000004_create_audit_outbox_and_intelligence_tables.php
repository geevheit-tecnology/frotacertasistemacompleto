<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('group_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->uuid('branch_id')->nullable();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('occurred_at')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->string('entity', 120);
            $table->uuid('entity_id')->nullable();
            $table->string('action', 80);
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->string('origin', 120);
            $table->timestamps();
            $table->index(['entity', 'entity_id']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['group_id', 'company_id', 'branch_id', 'occurred_at']);
        });

        Schema::create('outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_name', 160);
            $table->string('aggregate_type', 120);
            $table->uuid('aggregate_id');
            $table->json('payload');
            $table->string('idempotency_key', 128)->unique();
            $table->string('status', 32)->default('PENDING');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('available_at')->useCurrent();
            $table->timestampTz('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['status', 'available_at']);
            $table->index('event_name');
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('type', 80);
            $table->string('category', 80);
            $table->string('entity', 120);
            $table->uuid('entity_id');
            $table->string('severity', 20);
            $table->timestampTz('alert_date');
            $table->timestampTz('predicted_date')->nullable();
            $table->timestampTz('deadline')->nullable();
            $table->string('origin', 120);
            $table->string('rule', 160);
            $table->text('message');
            $table->text('recommendation');
            $table->string('status', 32)->default('ABERTO');
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('evidence')->default('{}');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index(['entity', 'entity_id']);
            $table->index(['severity', 'deadline']);
        });

        Schema::create('insights', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->string('type', 60);
            $table->string('title', 180);
            $table->text('description');
            $table->json('evidence')->default('{}');
            $table->string('impact', 80)->nullable();
            $table->decimal('probability', 5, 2)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->text('recommendation')->nullable();
            $table->string('entity', 120);
            $table->uuid('entity_id');
            $table->string('status', 32)->default('OPEN');
            $table->timestamps();
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index(['entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insights');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('outbox');
        Schema::dropIfExists('audit_logs');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
