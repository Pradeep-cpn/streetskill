<?php

use App\Http\Controllers\Admin\ReportModerationController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\OtpRegisterController;
use App\Http\Controllers\Auth\OtpPasswordController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SwapRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('landing');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Auth::routes([
    'register' => false,
    'reset' => false,
]);

Route::middleware('guest')->group(function () {
    Route::get('/register', [OtpRegisterController::class, 'showEmailForm'])->name('register');
    Route::post('/register', [OtpRegisterController::class, 'sendOtp'])->name('register.send');
    Route::get('/register/verify', [OtpRegisterController::class, 'showVerifyForm'])->name('register.verify');
    Route::post('/register/verify', [OtpRegisterController::class, 'verifyOtp'])->name('register.verify.submit');
    Route::post('/register/resend', [OtpRegisterController::class, 'resendOtp'])->name('register.resend');
    Route::get('/register/password', [OtpRegisterController::class, 'showPasswordForm'])->name('register.password');
    Route::post('/register/password', [OtpRegisterController::class, 'store'])->name('register.store');

    Route::get('/password/forgot', [OtpPasswordController::class, 'showEmailForm'])->name('password.request');
    Route::post('/password/forgot', [OtpPasswordController::class, 'sendOtp'])->name('password.email');
    Route::get('/password/otp', [OtpPasswordController::class, 'showVerifyForm'])->name('password.otp.verify');
    Route::post('/password/otp', [OtpPasswordController::class, 'verifyOtp'])->name('password.otp.check');
    Route::post('/password/otp/resend', [OtpPasswordController::class, 'resendOtp'])->name('password.otp.resend');
    Route::get('/password/reset', [OtpPasswordController::class, 'showResetForm'])->name('password.otp.reset');
    Route::post('/password/reset', [OtpPasswordController::class, 'reset'])->name('password.otp.update');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/user/{slug}', [PublicProfileController::class, 'show'])->name('public.profile');

Route::middleware(['auth'])->group(function () {
    Route::get('/session/ping', [HomeController::class, 'sessionPing'])->name('session.ping');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/profile/activity/export', [ActivityLogController::class, 'export'])->name('profile.activity.export');
    Route::post('/profile/verification', [ProfileController::class, 'requestVerification'])->name('profile.verification.request');
    Route::post('/profile/location/hide', [ProfileController::class, 'hideLocationTags'])->name('profile.location.hide');
    Route::post('/profile/location/show', [ProfileController::class, 'showLocationTags'])->name('profile.location.show');

    Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace');
    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('/connections', [ConnectionController::class, 'send'])->name('connections.send');
    Route::post('/connections/{connection}/accept', [ConnectionController::class, 'accept'])->name('connections.accept');
    Route::post('/connections/{connection}/reject', [ConnectionController::class, 'reject'])->name('connections.reject');

    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::post('/map/tags', [MapController::class, 'store'])->name('map.store');
    Route::post('/map/tags/{tag}/delete', [MapController::class, 'destroy'])->name('map.destroy');
    Route::post('/map/tags/delete-all', [MapController::class, 'destroyAll'])->name('map.destroyAll');

    Route::post('/endorse/{user}', [EndorsementController::class, 'store'])->name('endorse.store');

    Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
    Route::post('/challenges/{challenge}/submit', [ChallengeController::class, 'storeSubmission'])->name('challenges.submit');
    Route::post('/challenges/vote/{submission}', [ChallengeController::class, 'vote'])->name('challenges.vote');

    Route::get('/roadmaps', [RoadmapController::class, 'index'])->name('roadmaps.index');
    Route::get('/roadmaps/create', [RoadmapController::class, 'create'])->name('roadmaps.create');
    Route::post('/roadmaps', [RoadmapController::class, 'store'])->name('roadmaps.store');
    Route::get('/roadmaps/{roadmap}', [RoadmapController::class, 'show'])->name('roadmaps.show');
    Route::post('/roadmaps/{roadmap}/follow', [RoadmapController::class, 'follow'])->name('roadmaps.follow');
    Route::post('/roadmap-steps/{step}/toggle', [RoadmapController::class, 'toggleStep'])->name('roadmaps.steps.toggle');

    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('/rooms/{room}/message', [RoomController::class, 'postMessage'])->name('rooms.message');

    Route::post('/swap-request', [SwapRequestController::class, 'store'])->name('swap.request');
    Route::get('/swap-request/confirm/{swap}', [SwapRequestController::class, 'confirm'])->name('swap.confirm');
    Route::get('/requests', [SwapRequestController::class, 'dashboard'])->name('requests.dashboard');
    Route::post('/requests/{id}/{status}', [SwapRequestController::class, 'updateStatus'])->name('requests.update');

    Route::post('/rate', [RatingController::class, 'store'])->name('rate.user');
    Route::post('/rate/{rating}', [RatingController::class, 'update'])->name('rate.user.update');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/blocks', [BlockController::class, 'store'])->name('blocks.store');
    Route::delete('/blocks/{user}', [BlockController::class, 'destroy'])->name('blocks.destroy');

    Route::get('/inbox', [MessageController::class, 'inbox'])->name('chat.inbox');
    Route::post('/chat/send', [MessageController::class, 'send']);
    Route::post('/chat/typing/{id}', [MessageController::class, 'typing']);
    Route::get('/chat/fetch/{id}', [MessageController::class, 'fetch']);
    Route::get('/chat/{id}', [MessageController::class, 'show'])->name('chat.page');

    Route::prefix('admin')->name('admin.')->middleware('primary.admin')->group(function () {
        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/reports', [ReportModerationController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/status', [ReportModerationController::class, 'updateStatus'])->name('reports.status');
        Route::post('/verification/{user}/badge', [\App\Http\Controllers\Admin\AdminVerificationController::class, 'update'])->name('verification.update');
    });

});
