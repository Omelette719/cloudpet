<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('service_type', ['COMPUTE', 'STORAGE', 'DATABASE'])->index();
            $table->string('name', 100);
            $table->integer('vcpu')->nullable();
            $table->integer('ram')->nullable();
            $table->integer('storage')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
