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
        Schema::create('atc_position_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atc_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_occurrence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('user_cid');
            $table->foreign('user_cid')->references('cid')->on('users')->cascadeOnDelete();
            $table->text('availability')->nullable();
            $table->timestamps();

            $table->unique(['atc_position_id', 'event_occurrence_id', 'user_cid'], 'atc_position_interests_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atc_position_interests');
    }
};
