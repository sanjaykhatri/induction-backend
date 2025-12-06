<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the enum to include 'pending' status
        DB::statement("ALTER TABLE submissions MODIFY COLUMN status ENUM('in_progress', 'pending', 'completed') DEFAULT 'in_progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE submissions MODIFY COLUMN status ENUM('in_progress', 'completed') DEFAULT 'in_progress'");
    }
};
