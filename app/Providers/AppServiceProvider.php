<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Report;
use App\Models\SwapRequest;
use App\Support\AdminPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $pendingCount = 0;
            $unreadMessageCount = 0;
            $openReportCount = 0;
            $isPrimaryAdmin = false;

            if (Auth::check()) {
                $pendingCount = SwapRequest::where('to_user_id', Auth::id())
                    ->where('status', 'pending')
                    ->count();

                $unreadMessageCount = Message::where('to_user_id', Auth::id())
                    ->whereNull('read_at')
                    ->count();

                $isPrimaryAdmin = AdminPolicy::isPrimaryAdmin(Auth::user());

                if ($isPrimaryAdmin) {
                    $openReportCount = Report::where('status', 'open')->count();
                }
            }

            $view->with(compact('pendingCount', 'unreadMessageCount', 'openReportCount', 'isPrimaryAdmin'));
        });
    }
}
