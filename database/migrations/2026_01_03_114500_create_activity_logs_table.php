<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // User yang melakukan aksi (nullable untuk aksi sistem/guest)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            // Tipe aksi: created, updated, deleted, login, logout, etc.
            $table->string('action', 50);
            
            // Deskripsi aksi yang dilakukan
            $table->string('description')->nullable();
            
            // Model/Entity yang terkena aksi (polymorphic)
            $table->nullableMorphs('subject');
            
            // Data sebelum perubahan (untuk update/delete)
            $table->json('old_values')->nullable();
            
            // Data setelah perubahan (untuk create/update)
            $table->json('new_values')->nullable();
            
            // Properties tambahan (meta data)
            $table->json('properties')->nullable();
            
            // IP Address user
            $table->string('ip_address', 45)->nullable();
            
            // User Agent browser
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes untuk query yang sering digunakan
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
