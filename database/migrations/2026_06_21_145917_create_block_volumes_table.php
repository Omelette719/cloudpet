<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_volumes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('compute_instance_id')->nullable();
            $table->foreign('compute_instance_id')->references('id')->on('compute_instances')->nullOnDelete();

            $table->string('volume_name');
            $table->integer('size_gb');
            $table->string('status')->default('PROVISIONING');
            $table->string('provider_volume_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_volumes');
    }
};
