<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'storage_quota_gb')) {
                $table->integer('storage_quota_gb')->default(30)->after('account_status');
            }
            if (!Schema::hasColumn('users', 'storage_used_gb')) {
                $table->decimal('storage_used_gb', 10, 3)->default(0)->after('storage_quota_gb');
            }
            if (!Schema::hasColumn('users', 'storage_plan')) {
                $table->string('storage_plan')->default('free')->after('storage_used_gb');
            }
            if (!Schema::hasColumn('users', 'storage_plan_expires_at')) {
                $table->timestamp('storage_plan_expires_at')->nullable()->after('storage_plan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['storage_quota_gb','storage_used_gb','storage_plan','storage_plan_expires_at']);
        });
    }
};