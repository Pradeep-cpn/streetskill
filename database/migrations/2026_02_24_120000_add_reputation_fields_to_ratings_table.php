<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->index('from_user_id');
            $table->index('to_user_id');
            $table->dropUnique('ratings_from_user_id_to_user_id_unique');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('swap_request_id')->nullable()->after('to_user_id')->constrained('swap_requests')->nullOnDelete();
            $table->string('skill', 120)->nullable()->after('swap_request_id');
            $table->boolean('verified')->default(false)->after('review');
            $table->decimal('weight', 4, 2)->default(1.0)->after('verified');

            $table->unique(['from_user_id', 'to_user_id', 'swap_request_id']);
            $table->index(['to_user_id', 'verified']);
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['to_user_id', 'verified']);
            $table->dropUnique(['from_user_id', 'to_user_id', 'swap_request_id']);
            $table->dropColumn(['swap_request_id', 'skill', 'verified', 'weight']);

            $table->unique(['from_user_id', 'to_user_id']);
            $table->dropIndex(['from_user_id']);
            $table->dropIndex(['to_user_id']);
        });
    }
};
