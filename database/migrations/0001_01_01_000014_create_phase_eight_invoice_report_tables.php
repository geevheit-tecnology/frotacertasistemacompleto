<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $this->tenantColumns($table);
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignUuid('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            $table->string('number', 80);
            $table->string('series', 40)->nullable();
            $table->string('access_key', 80)->nullable();
            $table->date('issued_at');
            $table->decimal('amount', 12, 2);
            $table->string('category', 80)->default('UNCLASSIFIED');
            $table->string('file_path', 500)->nullable();
            $table->string('status', 32)->default('PENDING');
            $table->json('flags')->default('[]');
            $table->timestamps();
            $table->unique(['company_id', 'number', 'series']);
            $table->index(['group_id', 'company_id', 'branch_id', 'status']);
            $table->index(['vehicle_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }

    private function tenantColumns(Blueprint $table): void
    {
        $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
        $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
        $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
    }
};
