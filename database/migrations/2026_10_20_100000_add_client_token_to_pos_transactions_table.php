<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->string('client_token', 64)->nullable()->after('transaction_no');
            $table->unique(['mitra_id', 'client_token'], 'pos_transactions_mitra_client_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropUnique('pos_transactions_mitra_client_token_unique');
            $table->dropColumn('client_token');
        });
    }
};
