<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate ratings, keep the latest (highest id) per user pair.
        DB::statement('
            DELETE r FROM ratings r
            INNER JOIN (
                SELECT MAX(id) AS max_id, from_user_id, to_user_id
                FROM ratings
                GROUP BY from_user_id, to_user_id
                HAVING COUNT(*) > 1
            ) d
            ON r.from_user_id = d.from_user_id
           AND r.to_user_id = d.to_user_id
           AND r.id <> d.max_id
        ');

        Schema::table('ratings', function (Blueprint $table) {
            $table->unique(['from_user_id', 'to_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique(['from_user_id', 'to_user_id']);
        });
    }
};
