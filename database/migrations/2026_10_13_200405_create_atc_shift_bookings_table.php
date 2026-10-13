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
        Schema::create('atc_shift_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atc_shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_occurrence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('user_cid');
            $table->foreign('user_cid')->references('cid')->on('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['atc_shift_id', 'event_occurrence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atc_shift_bookings');
    }
};
