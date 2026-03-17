<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            $table->string('regency_code')->nullable()->after('address');
            $table->string('district_code')->nullable()->after('regency_code');

            $table->foreign('regency_code')->references('code')->on('regencies')->onDelete('set null');
            $table->foreign('district_code')->references('code')->on('districts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mitras', function (Blueprint $table) {
            $table->dropForeign(['regency_code']);
            $table->dropForeign(['district_code']);
            $table->dropColumn(['regency_code', 'district_code']);
        });
    }
};
