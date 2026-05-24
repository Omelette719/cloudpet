<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('service_module', 100);
            $table->enum('error_level', ['WARNING', 'ERROR', 'CRITICAL'])->default('ERROR');
            $table->text('message');
            $table->json('stack_trace')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('resolved');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
