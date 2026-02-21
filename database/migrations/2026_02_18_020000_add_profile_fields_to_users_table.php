<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('headline')->nullable()->after('bio');
            $table->json('portfolio_links')->nullable()->after('skills_wanted');
            $table->string('website_url')->nullable()->after('portfolio_links');
            $table->string('linkedin_url')->nullable()->after('website_url');
            $table->string('instagram_url')->nullable()->after('linkedin_url');
            $table->string('youtube_url')->nullable()->after('instagram_url');
            $table->string('verified_badge')->nullable()->after('rating');
            $table->timestamp('verification_requested_at')->nullable()->after('verified_badge');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'headline',
                'portfolio_links',
                'website_url',
                'linkedin_url',
                'instagram_url',
                'youtube_url',
                'verified_badge',
                'verification_requested_at',
            ]);
        });
    }
};
