<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('challenge_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['submission_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_votes');
    }
};
