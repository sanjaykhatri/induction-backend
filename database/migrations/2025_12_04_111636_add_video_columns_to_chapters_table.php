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
        Schema::table('chapters', function (Blueprint $table) {
            // Add video file storage columns if they don't exist
            if (!Schema::hasColumn('chapters', 'video_path')) {
                $table->string('video_path')->nullable()->after('video_url');
            }
            if (!Schema::hasColumn('chapters', 'video_filename')) {
                $table->string('video_filename')->nullable()->after('video_path');
            }
            if (!Schema::hasColumn('chapters', 'video_duration')) {
                $table->integer('video_duration')->nullable()->after('video_filename');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            // Remove video file storage columns
            if (Schema::hasColumn('chapters', 'video_path')) {
                $table->dropColumn('video_path');
            }
            if (Schema::hasColumn('chapters', 'video_filename')) {
                $table->dropColumn('video_filename');
            }
            if (Schema::hasColumn('chapters', 'video_duration')) {
                $table->dropColumn('video_duration');
            }
        });
    }
};
