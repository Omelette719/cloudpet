<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('managed_databases');

        Schema::create('managed_databases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('plan_id')->nullable();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();

            $table->string('engine', 50);
            $table->string('db_name', 100);
            $table->string('db_user', 100);
            $table->string('db_password', 255);
            $table->string('host', 255)->nullable();
            $table->integer('port')->nullable();
            $table->string('rds_identifier')->nullable();

            $table->string('status')->default('PROVISIONING')->index();
            $table->json('metadata')->nullable();
            $table->text('provision_log')->nullable();

            $table->decimal('price_per_hour', 10, 2)->default(0);
            $table->decimal('usage_hours', 12, 4)->default(0);
            $table->decimal('cost', 12, 2)->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('managed_databases');
    }
};
