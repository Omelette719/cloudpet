<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_state_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('resource_type', 50);
            $table->uuid('resource_id');
            $table->string('old_state', 50)->nullable();
            $table->string('new_state', 50);
            $table->enum('triggered_by', ['USER_API', 'SYSTEM_CRON', 'ADMIN'])->default('USER_API');
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index(['resource_id', 'resource_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_state_logs');
    }
};
