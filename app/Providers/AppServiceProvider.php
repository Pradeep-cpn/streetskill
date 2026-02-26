<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\Notification;
use App\Models\Report;
use App\Models\SwapRequest;
use App\Support\AdminPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            $pendingCount = 0;
            $unreadMessageCount = 0;
            $openReportCount = 0;
            $notificationCount = 0;
            $notifications = collect();
            $isPrimaryAdmin = false;

            $user = Auth::user();
            if ($user) {
                $userId = (int) $user->id;
                $cacheKey = "layout:metrics:v1:user:{$userId}";
                $cached = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($user, $userId) {
                    $pendingCount = SwapRequest::query()
                        ->where('to_user_id', $userId)
                        ->where('status', 'pending')
                        ->count();

                    $unreadMessageCount = Message::query()
                        ->where('to_user_id', $userId)
                        ->whereNull('read_at')
                        ->count();

                    $notificationCount = 0;
                    $notifications = collect();
                    if (Schema::hasTable('notifications')) {
                        $notificationCount = Notification::query()
                            ->where('user_id', $userId)
                            ->whereNull('read_at')
                            ->count();

                        $notifications = Notification::query()
                            ->where('user_id', $userId)
                            ->latest()
                            ->limit(6)
                            ->get();
                    }

                    $isPrimaryAdmin = AdminPolicy::isPrimaryAdmin($user);
                    $openReportCount = 0;
                    if ($isPrimaryAdmin) {
                        $openReportCount = Report::query()
                            ->where('status', 'open')
                            ->count();
                    }

                    return compact(
                        'pendingCount',
                        'unreadMessageCount',
                        'openReportCount',
                        'notificationCount',
                        'notifications',
                        'isPrimaryAdmin'
                    );
                });

                $pendingCount = $cached['pendingCount'];
                $unreadMessageCount = $cached['unreadMessageCount'];
                $openReportCount = $cached['openReportCount'];
                $notificationCount = $cached['notificationCount'];
                $notifications = $cached['notifications'];
                $isPrimaryAdmin = $cached['isPrimaryAdmin'];
            }

            $view->with(compact(
                'pendingCount',
                'unreadMessageCount',
                'openReportCount',
                'notificationCount',
                'notifications',
                'isPrimaryAdmin'
            ));
        });
    }
}
