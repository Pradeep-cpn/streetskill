<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('message_type', 20)->default('text')->after('read_at');
            $table->string('image_path')->nullable()->after('message_type');
            $table->string('image_mime', 100)->nullable()->after('image_path');
            $table->unsignedInteger('image_size')->nullable()->after('image_mime');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['message_type', 'image_path', 'image_mime', 'image_size']);
        });
    }
};
