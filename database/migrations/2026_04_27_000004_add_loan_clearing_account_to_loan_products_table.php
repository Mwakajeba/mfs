<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->foreignId('loan_clearing_account_id')
                ->nullable()
                ->after('interest_revenue_account_id')
                ->constrained('chart_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_clearing_account_id');
        });
    }
};

