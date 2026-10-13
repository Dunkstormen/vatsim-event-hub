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
        Schema::table('events', function (Blueprint $table) {
            $table->string('atc_roster_type')->nullable()->after('recurrence_frequency');
            $table->unsignedInteger('atc_interest_opens_hours_before')->nullable()->after('atc_roster_type');
            $table->unsignedInteger('atc_interest_closes_hours_before')->nullable()->after('atc_interest_opens_hours_before');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['atc_roster_type', 'atc_interest_opens_hours_before', 'atc_interest_closes_hours_before']);
        });
    }
};
