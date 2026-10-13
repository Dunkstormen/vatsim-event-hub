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
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_cid');
            $table->foreign('user_cid')->references('cid')->on('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            // Null fir_id means the role assignment is global and applies across all FIRs.
            $table->foreignId('fir_id')->nullable()->constrained('firs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_cid', 'role_id', 'fir_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
