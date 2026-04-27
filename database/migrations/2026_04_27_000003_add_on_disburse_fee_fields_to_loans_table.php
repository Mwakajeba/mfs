<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('on_disburse_fee_id')->nullable()->after('reference')->constrained('fees')->nullOnDelete();
            $table->decimal('on_disburse_fee_amount', 15, 2)->nullable()->after('on_disburse_fee_id');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('on_disburse_fee_id');
            $table->dropColumn('on_disburse_fee_amount');
        });
    }
};

