<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table): void {
            $table->unique(['company_id', 'entity', 'entity_id', 'rule', 'status'], 'alerts_open_rule_unique');
        });

        Schema::table('insights', function (Blueprint $table): void {
            $table->unique(['company_id', 'entity', 'entity_id', 'type', 'status'], 'insights_open_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('insights', function (Blueprint $table): void {
            $table->dropUnique('insights_open_type_unique');
        });

        Schema::table('alerts', function (Blueprint $table): void {
            $table->dropUnique('alerts_open_rule_unique');
        });
    }
};
