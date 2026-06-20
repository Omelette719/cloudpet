<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('compute_instances', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('stopped_at')->nullable()->after('started_at');
            $table->decimal('usage_hours', 10, 2)->nullable()->after('stopped_at');
            $table->decimal('price_per_hour', 10, 2)->nullable()->after('usage_hours');
            $table->decimal('cost', 12, 2)->nullable()->after('price_per_hour');
        });
    }

    public function down()
    {
        Schema::table('compute_instances', function (Blueprint $table) {
            $table->dropColumn(['started_at','stopped_at','usage_hours','price_per_hour','cost']);
        });
    }
};
