<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('managed_databases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('plan_id')->nullable();
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
            $table->string('engine', 50);
            $table->string('db_name', 100);
            $table->string('db_user', 100);
            $table->string('db_password', 255);
            $table->string('host', 255)->nullable();
            $table->integer('port')->nullable();
            $table->enum('status', ['CREATING', 'ACTIVE', 'SUSPENDED', 'TERMINATED'])->default('CREATING')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_databases');
    }
};
