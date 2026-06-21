<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compute_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('compute_instances', 'os')) {
                $table->string('os')->nullable()->after('plan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compute_instances', function (Blueprint $table) {
            $table->dropColumn('os');
        });
    }
};