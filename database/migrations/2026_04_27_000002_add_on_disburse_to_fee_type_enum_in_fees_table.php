<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE fees MODIFY COLUMN fee_type ENUM('fixed', 'percentage', 'range', 'on_disburse') DEFAULT 'fixed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fees MODIFY COLUMN fee_type ENUM('fixed', 'percentage', 'range') DEFAULT 'fixed'");
    }
};

