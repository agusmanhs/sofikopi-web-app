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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size'); // in bytes
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('collection')->nullable(); // e.g., 'avatars', 'products'
            $table->json('meta')->nullable(); // extra info (width, height, etc)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
