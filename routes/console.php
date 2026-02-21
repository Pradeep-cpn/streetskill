<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tags:cleanup', function () {
    $deleted = DB::table('location_tags')
        ->where('expires_at', '<', now())
        ->delete();

    $this->info("Deleted {$deleted} expired location tags.");
})->purpose('Delete expired location tags');

Schedule::command('tags:cleanup')->hourly();
