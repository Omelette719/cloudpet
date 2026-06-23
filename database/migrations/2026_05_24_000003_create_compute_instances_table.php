<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compute_instances')) {
            Schema::create('compute_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('plan')->nullable();
            $table->json('metadata')->nullable();
            $table->string('os', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['PROVISIONING', 'RUNNING', 'STOPPED', 'TERMINATED'])->default('PROVISIONING')->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compute_instances');
    }
};
