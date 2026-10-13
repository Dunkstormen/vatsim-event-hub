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
        Schema::create('atc_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atc_position_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('start_offset_minutes');
            $table->unsignedInteger('end_offset_minutes');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atc_shifts');
    }
};
