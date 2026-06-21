<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_volumes', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke User pemilik
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Relasi ke Compute Instance. 
            // Dibuat nullable() karena volume bisa saja sedang tidak menempel (AVAILABLE).
            // nullOnDelete() memastikan jika Compute Instance dihapus, Block Volume TIDAK ikut terhapus, melainkan hanya terlepas (DETACHED).
            $table->foreignId('compute_instance_id')->nullable()->constrained('compute_instances')->nullOnDelete();
            
            $table->string('volume_name');
            $table->integer('size_gb');
            
            // Status: PROVISIONING, AVAILABLE, ATTACHED, DELETING
            $table->string('status')->default('PROVISIONING'); 
            
            // Untuk menyimpan ID referensi dari engine simulator (Ministack)
            $table->string('provider_volume_id')->nullable(); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_volumes');
    }
};