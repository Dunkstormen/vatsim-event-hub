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
        Schema::table('event_occurrences', function (Blueprint $table) {
            $table->timestamp('atc_roster_published_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_occurrences', function (Blueprint $table) {
            $table->dropColumn('atc_roster_published_at');
        });
    }
};
