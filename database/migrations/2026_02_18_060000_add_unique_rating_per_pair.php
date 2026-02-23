<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate ratings, keep latest record per pair.
        // Use driver-specific SQL to avoid MySQL 1093 error.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                DELETE r1 FROM ratings r1
                INNER JOIN ratings r2
                    ON r1.from_user_id = r2.from_user_id
                    AND r1.to_user_id = r2.to_user_id
                    AND r1.id < r2.id
            ");
        } else {
            DB::statement("
                DELETE FROM ratings
                WHERE id NOT IN (
                    SELECT MAX(id)
                    FROM ratings
                    GROUP BY from_user_id, to_user_id
                )
            ");
        }

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
