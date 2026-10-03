<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox', function (Blueprint $table): void {
            $table->timestampTz('locked_at')->nullable()->after('available_at');
            $table->string('locked_by', 120)->nullable()->after('locked_at');
            $table->timestampTz('dead_lettered_at')->nullable()->after('published_at');
            $table->index(['status', 'available_at', 'locked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('outbox', function (Blueprint $table): void {
            $table->dropIndex(['status', 'available_at', 'locked_at']);
            $table->dropColumn(['locked_at', 'locked_by', 'dead_lettered_at']);
        });
    }
};
