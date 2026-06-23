<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_buckets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('bucket_name', 63)->unique();
            $table->string('access_key', 100);
            $table->string('secret_key', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_buckets');
    }
};
