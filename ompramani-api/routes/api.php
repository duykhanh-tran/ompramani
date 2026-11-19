<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\AlmanacController;
use App\Http\Controllers\Api\V1\ArticleCategoryController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\BuddhistFigureController;
use App\Http\Controllers\Api\V1\BuddhistVideoController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\CalendarEventController;
use App\Http\Controllers\Api\V1\DharmaJourneyController;
use App\Http\Controllers\Api\V1\MantraController;
use App\Http\Controllers\Api\V1\MyPrayerIntentionController;
use App\Http\Controllers\Api\V1\MyPrayerResponseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\PracticePlanController;
use App\Http\Controllers\Api\V1\PracticePlanItemController;
use App\Http\Controllers\Api\V1\PracticePlanOccurrenceController;
use App\Http\Controllers\Api\V1\PracticeReminderController;
use App\Http\Controllers\Api\V1\PracticeReminderSettingsController;
use App\Http\Controllers\Api\V1\PracticeSessionController;
use App\Http\Controllers\Api\V1\PracticeStreakController;
use App\Http\Controllers\Api\V1\PrayerParticipationController;
use App\Http\Controllers\Api\V1\PrayerResponseReportController;
use App\Http\Controllers\Api\V1\PrayerRoomController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PublicPrayerIntentionController;
use App\Http\Controllers\Api\V1\PublicPrayerResponseController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SepayPaymentController;
use App\Http\Controllers\Api\V1\ShopOrderController;
use App\Http\Controllers\Api\V1\ShopProductController;
use App\Http\Controllers\Api\V1\SutraBookmarkController;
use App\Http\Controllers\Api\V1\SutraController;
use App\Http\Controllers\Api\V1\SutraReadingProgressController;
use App\Http\Controllers\Api\V1\SutraSectionController;
use App\Http\Controllers\Api\V1\SutraVersionController;
use App\Http\Controllers\Api\V1\TempleController;
use App\Http\Controllers\Api\V1\TraditionController;
use App\Http\Controllers\Api\V1\UserFavoriteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api'])->group(function (): void {
    Route::get('/search', [SearchController::class, 'search'])->name('api.v1.search');
    Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->name('api.v1.search.suggestions');

    Route::get('/traditions', [TraditionController::class, 'index'])->name('api.v1.traditions.index');

    // Temple & Buddhist Center Directory Core (Task 38 & Task 39)
    Route::get('/temples/types', [TempleController::class, 'types'])->name('api.v1.temples.types');
    Route::get('/temples/filters', [TempleController::class, 'filters'])->name('api.v1.temples.filters');
    Route::get('/temples', [TempleController::class, 'index'])->name('api.v1.temples.index');
    Route::get('/temples/{slug}', [TempleController::class, 'show'])->name('api.v1.temples.show');
    Route::get('/sutras', [SutraController::class, 'index'])->name('api.v1.sutras.index');
    Route::get('/sutras/{slug}', [SutraController::class, 'show'])->name('api.v1.sutras.show');
    Route::get('/sutras/{slug}/sections', [SutraController::class, 'sections'])->name('api.v1.sutras.sections');
    Route::get('/sutras/{slug}/versions', [SutraVersionController::class, 'index'])->name('api.v1.sutras.versions');
    Route::get('/sutra-versions/{id}/sections', [SutraVersionController::class, 'sections'])->name('api.v1.sutra-versions.sections');
    Route::get('/sutra-sections/{id}', [SutraSectionController::class, 'show'])->name('api.v1.sutra-sections.show');

    Route::get('/buddhist-figures', [BuddhistFigureController::class, 'index'])->name('api.v1.buddhist-figures.index');
    Route::get('/buddhist-figures/{slug}', [BuddhistFigureController::class, 'show'])->name('api.v1.buddhist-figures.show');

    Route::get('/mantras', [MantraController::class, 'index'])->name('api.v1.mantras.index');
    Route::get('/mantras/{slug}', [MantraController::class, 'show'])->name('api.v1.mantras.show');
    Route::get('/mantras/{slug}/versions', [MantraController::class, 'versions'])->name('api.v1.mantras.versions');
    Route::get('/mantra-versions/{id}', [MantraController::class, 'versionDetail'])->name('api.v1.mantra-versions.show');

    Route::get('/article-categories', [ArticleCategoryController::class, 'index'])->name('api.v1.article-categories.index');
    Route::get('/articles', [ArticleController::class, 'index'])->name('api.v1.articles.index');
    Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('api.v1.articles.show');

    // Video Library Public Routes (Task 41)
    Route::get('/videos/categories', [BuddhistVideoController::class, 'categories'])->name('api.v1.videos.categories');
    Route::get('/videos/topics', [BuddhistVideoController::class, 'topics'])->name('api.v1.videos.topics');
    Route::get('/videos', [BuddhistVideoController::class, 'index'])->name('api.v1.videos.index');
    Route::get('/videos/{slug}', [BuddhistVideoController::class, 'show'])->name('api.v1.videos.show');

    // Shop & Product Catalog Public Routes (Task 43 & 44)
    Route::prefix('shop')->group(function (): void {
        Route::get('/categories', [ShopProductController::class, 'categories'])->name('api.v1.shop.categories');
        Route::get('/products', [ShopProductController::class, 'index'])->name('api.v1.shop.products.index');
        Route::get('/products/{slug}', [ShopProductController::class, 'show'])->name('api.v1.shop.products.show');
        Route::post('/cart/preview', [ShopProductController::class, 'previewCart'])->name('api.v1.shop.cart.preview');
        Route::post('/sepay/ipn', [SepayPaymentController::class, 'handleIpn'])->name('api.v1.shop.sepay.ipn');
    });

    // Shop Checkout & Order Management Authenticated Routes (Task 45 & Task 46)
    Route::middleware('auth:sanctum')->prefix('shop')->group(function (): void {
        Route::post('/checkout/preview', [ShopOrderController::class, 'checkoutPreview'])->name('api.v1.shop.checkout.preview');
        Route::post('/orders', [ShopOrderController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('api.v1.shop.orders.store');
        Route::get('/me/orders', [ShopOrderController::class, 'index'])->name('api.v1.shop.me.orders.index');
        Route::get('/me/orders/{publicId}', [ShopOrderController::class, 'show'])->name('api.v1.shop.me.orders.show');
        Route::post('/me/orders/{publicId}/cancel', [ShopOrderController::class, 'cancel'])
            ->middleware('throttle:10,1')
            ->name('api.v1.shop.me.orders.cancel');

        Route::post('/orders/{publicId}/switch-to-cod', [ShopOrderController::class, 'switchToCod'])
            ->middleware('throttle:10,1')
            ->name('api.v1.shop.orders.switch-to-cod');

        Route::get('/orders/{publicId}/payment-status', [SepayPaymentController::class, 'getPaymentStatus'])
            ->name('api.v1.shop.orders.payment-status');
        Route::post('/orders/{publicId}/sepay-retry', [SepayPaymentController::class, 'retryPayment'])
            ->middleware('throttle:10,1')
            ->name('api.v1.shop.orders.sepay-retry');
    });

    // Calendar Core Public Routes
    Route::prefix('calendar')->group(function (): void {
        Route::get('/solar-to-lunar', [CalendarController::class, 'solarToLunar'])->name('api.v1.calendar.solar-to-lunar');
        Route::get('/lunar-to-solar', [CalendarController::class, 'lunarToSolar'])->name('api.v1.calendar.lunar-to-solar');
        Route::get('/day', [CalendarController::class, 'day'])->name('api.v1.calendar.day');
        Route::get('/week', [CalendarController::class, 'week'])->name('api.v1.calendar.week');
        Route::get('/month', [CalendarController::class, 'month'])->name('api.v1.calendar.month');
        Route::get('/config', [CalendarController::class, 'config'])->name('api.v1.calendar.config');

        // Buddhist Calendar Events & Religious Observances (Task 28)
        Route::get('/event-types', [CalendarEventController::class, 'types'])->name('api.v1.calendar.event-types');
        Route::get('/events', [CalendarEventController::class, 'index'])->name('api.v1.calendar.events.index');
        Route::get('/events/day', [CalendarEventController::class, 'day'])->name('api.v1.calendar.events.day');
        Route::get('/events/month', [CalendarEventController::class, 'month'])->name('api.v1.calendar.events.month');
        Route::get('/events/{slug}', [CalendarEventController::class, 'show'])->name('api.v1.calendar.events.show');

        // Traditional Almanac Data Provider (Task 30)
        Route::prefix('almanac')->group(function (): void {
            Route::get('/day', [AlmanacController::class, 'day'])->name('api.v1.calendar.almanac.day');
            Route::get('/month', [AlmanacController::class, 'month'])->name('api.v1.calendar.almanac.month');
            Route::get('/methods', [AlmanacController::class, 'methods'])->name('api.v1.calendar.almanac.methods');
            Route::get('/config', [AlmanacController::class, 'config'])->name('api.v1.calendar.almanac.config');
        });
    });

    // Prayer Intentions Community Public Routes (Task 32 & Task 34)
    Route::prefix('prayer-intentions')->group(function (): void {
        Route::get('/categories', [PublicPrayerIntentionController::class, 'categories'])->name('api.v1.prayer-intentions.categories');
        Route::get('/', [PublicPrayerIntentionController::class, 'index'])->name('api.v1.prayer-intentions.index');
        Route::get('/{publicId}', [PublicPrayerIntentionController::class, 'show'])->name('api.v1.prayer-intentions.show');

        // Community Responses (Task 34)
        Route::get('/{publicId}/responses', [PublicPrayerResponseController::class, 'index'])->name('api.v1.prayer-intentions.responses.index');
        Route::post('/{publicId}/responses', [PublicPrayerResponseController::class, 'store'])
            ->middleware(['auth:sanctum', 'throttle:15,1'])
            ->name('api.v1.prayer-intentions.responses.store');
    });

    // Prayer Response Safety Report (Task 34)
    Route::post('/prayer-responses/{publicId}/reports', [PrayerResponseReportController::class, 'store'])
        ->middleware(['auth:sanctum', 'throttle:15,1'])
        ->name('api.v1.prayer-responses.reports.store');

    // Unified Community Prayer Room Routes (Hotfix: Single Room Experience)
    Route::prefix('prayer-room')->group(function (): void {
        Route::get('/messages', [PrayerRoomController::class, 'index'])->name('api.v1.prayer-room.messages.index');
        Route::post('/messages', [PrayerRoomController::class, 'store'])
            ->middleware(['auth:sanctum', 'throttle:15,1'])
            ->name('api.v1.prayer-room.messages.store');
        Route::delete('/messages/{publicId}', [PrayerRoomController::class, 'destroy'])
            ->middleware(['auth:sanctum', 'throttle:30,1'])
            ->name('api.v1.prayer-room.messages.destroy');
        Route::post('/messages/{publicId}/report', [PrayerRoomController::class, 'report'])
            ->middleware(['auth:sanctum', 'throttle:15,1'])
            ->name('api.v1.prayer-room.messages.report');
    });

    // Authentication Routes
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
            ->middleware('throttle:6,1')
            ->name('api.v1.auth.forgot-password');
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
            ->middleware('throttle:6,1')
            ->name('api.v1.auth.reset-password');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1')
                ->name('api.v1.auth.email.resend');
            Route::get('/email/status', [EmailVerificationController::class, 'status'])->name('api.v1.auth.email.status');
        });
    });

    // Profile & Account Security Routes (Protected)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('api.v1.profile.update');
        Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('api.v1.profile.avatar.upload');
        Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('api.v1.profile.avatar.delete');

        Route::patch('/account/email', [AccountSecurityController::class, 'updateEmail'])->name('api.v1.account.email.update');
        Route::patch('/account/password', [AccountSecurityController::class, 'changePassword'])->name('api.v1.account.password.change');

        // Practice Session Routes
        Route::prefix('me/practice-sessions')->group(function (): void {
            Route::get('/', [PracticeSessionController::class, 'index'])->name('api.v1.practice-sessions.index');
            Route::post('/', [PracticeSessionController::class, 'store'])->name('api.v1.practice-sessions.store');
            Route::get('/active', [PracticeSessionController::class, 'active'])->name('api.v1.practice-sessions.active');
            Route::get('/{id}', [PracticeSessionController::class, 'show'])->name('api.v1.practice-sessions.show');
            Route::post('/{id}/pause', [PracticeSessionController::class, 'pause'])->name('api.v1.practice-sessions.pause');
            Route::post('/{id}/resume', [PracticeSessionController::class, 'resume'])->name('api.v1.practice-sessions.resume');
            Route::post('/{id}/heartbeat', [PracticeSessionController::class, 'heartbeat'])->name('api.v1.practice-sessions.heartbeat');
            Route::patch('/{id}/progress', [PracticeSessionController::class, 'updateProgress'])->name('api.v1.practice-sessions.progress');
            Route::post('/{id}/complete', [PracticeSessionController::class, 'complete'])->name('api.v1.practice-sessions.complete');
            Route::post('/{id}/cancel', [PracticeSessionController::class, 'cancel'])->name('api.v1.practice-sessions.cancel');
        });

        // Sutra Reading Progress Routes
        Route::prefix('me/sutra-reading-progress')->group(function (): void {
            Route::get('/', [SutraReadingProgressController::class, 'index'])->name('api.v1.me.sutra-reading-progress.index');
        });
        Route::prefix('me/sutras/{slug}/reading-progress')->group(function (): void {
            Route::get('/', [SutraReadingProgressController::class, 'show'])->name('api.v1.me.sutras.reading-progress.show');
            Route::put('/', [SutraReadingProgressController::class, 'upsert'])->name('api.v1.me.sutras.reading-progress.upsert');
            Route::delete('/', [SutraReadingProgressController::class, 'destroy'])->name('api.v1.me.sutras.reading-progress.destroy');
        });

        // Sutra Bookmarks Routes
        Route::prefix('me/sutra-bookmarks')->group(function (): void {
            Route::get('/', [SutraBookmarkController::class, 'index'])->name('api.v1.me.sutra-bookmarks.index');
            Route::post('/', [SutraBookmarkController::class, 'store'])->name('api.v1.me.sutra-bookmarks.store');
            Route::delete('/by-target', [SutraBookmarkController::class, 'removeByTarget'])->name('api.v1.me.sutra-bookmarks.remove-target');
            Route::delete('/{id}', [SutraBookmarkController::class, 'destroy'])->name('api.v1.me.sutra-bookmarks.destroy');
            Route::get('/check/{slug}', [SutraBookmarkController::class, 'check'])->name('api.v1.me.sutra-bookmarks.check');
        });

        // Favorites & My Library Routes (Task 42)
        Route::prefix('me/favorites')->group(function (): void {
            Route::get('/', [UserFavoriteController::class, 'index'])->name('api.v1.me.favorites.index');
            Route::get('/status', [UserFavoriteController::class, 'status'])->name('api.v1.me.favorites.status');
            Route::put('/{type}/{publicId}', [UserFavoriteController::class, 'storeOrUpdate'])->name('api.v1.me.favorites.store');
            Route::delete('/{type}/{publicId}', [UserFavoriteController::class, 'destroy'])->name('api.v1.me.favorites.destroy');
        });

        // Dharma Journey Dashboard Routes
        Route::prefix('me/dharma-journey')->group(function (): void {
            Route::get('/', [DharmaJourneyController::class, 'index'])->name('api.v1.me.dharma-journey.index');
            Route::get('/summary', [DharmaJourneyController::class, 'summary'])->name('api.v1.me.dharma-journey.summary');
            Route::get('/activity', [DharmaJourneyController::class, 'activity'])->name('api.v1.me.dharma-journey.activity');
            Route::get('/breakdown', [DharmaJourneyController::class, 'breakdown'])->name('api.v1.me.dharma-journey.breakdown');
        });

        // Practice Streak System Route
        Route::get('me/practice-streak', [PracticeStreakController::class, 'show'])->name('api.v1.me.practice-streak.show');

        // Personal Prayer Intentions Management Routes (Task 32)
        Route::prefix('me/prayer-intentions')->group(function (): void {
            Route::get('/', [MyPrayerIntentionController::class, 'index'])->name('api.v1.me.prayer-intentions.index');
            Route::post('/', [MyPrayerIntentionController::class, 'store'])
                ->middleware('throttle:15,1')
                ->name('api.v1.me.prayer-intentions.store');
            Route::get('/{id}', [MyPrayerIntentionController::class, 'show'])->name('api.v1.me.prayer-intentions.show');
            Route::patch('/{id}', [MyPrayerIntentionController::class, 'update'])->name('api.v1.me.prayer-intentions.update');
            Route::delete('/{id}', [MyPrayerIntentionController::class, 'destroy'])->name('api.v1.me.prayer-intentions.destroy');
            Route::post('/{id}/submit', [MyPrayerIntentionController::class, 'submit'])->name('api.v1.me.prayer-intentions.submit');
            Route::post('/{id}/withdraw', [MyPrayerIntentionController::class, 'withdraw'])->name('api.v1.me.prayer-intentions.withdraw');
        });

        // Community Prayer Participation Routes (Task 33)
        Route::prefix('me/prayer-participations')->group(function (): void {
            Route::get('/', [PrayerParticipationController::class, 'history'])->name('api.v1.me.prayer-participations.history');
            Route::get('/status', [PrayerParticipationController::class, 'status'])->name('api.v1.me.prayer-participations.status');
            Route::put('/{publicId}', [PrayerParticipationController::class, 'participate'])
                ->middleware('throttle:30,1')
                ->name('api.v1.me.prayer-participations.participate');
            Route::delete('/{publicId}', [PrayerParticipationController::class, 'withdraw'])
                ->middleware('throttle:30,1')
                ->name('api.v1.me.prayer-participations.withdraw');
        });

        // Personal Prayer Responses Management Routes (Task 34)
        Route::prefix('me/prayer-responses')->group(function (): void {
            Route::get('/', [MyPrayerResponseController::class, 'index'])->name('api.v1.me.prayer-responses.index');
            Route::get('/{id}', [MyPrayerResponseController::class, 'show'])->name('api.v1.me.prayer-responses.show');
            Route::patch('/{id}', [MyPrayerResponseController::class, 'update'])->name('api.v1.me.prayer-responses.update');
            Route::delete('/{id}', [MyPrayerResponseController::class, 'destroy'])->name('api.v1.me.prayer-responses.destroy');
            Route::post('/{id}/withdraw', [MyPrayerResponseController::class, 'withdraw'])->name('api.v1.me.prayer-responses.withdraw');
            Route::post('/{id}/resubmit', [MyPrayerResponseController::class, 'resubmit'])->name('api.v1.me.prayer-responses.resubmit');
        });

        // Community Notifications Routes (Task 35)
        Route::prefix('me/notifications')->group(function (): void {
            Route::get('/', [NotificationController::class, 'index'])->name('api.v1.me.notifications.index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('api.v1.me.notifications.unread-count');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.v1.me.notifications.read-all');
            Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.v1.me.notifications.read');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('api.v1.me.notifications.destroy');
        });

        // Notification Preferences Routes (Task 35)
        Route::prefix('me/notification-preferences')->group(function (): void {
            Route::get('/', [NotificationPreferenceController::class, 'show'])->name('api.v1.me.notification-preferences.show');
            Route::put('/', [NotificationPreferenceController::class, 'update'])->name('api.v1.me.notification-preferences.update');
        });

        // Personal Practice Reminders Routes (Task 36)
        Route::prefix('me/practice-reminders')->group(function (): void {
            Route::get('/', [PracticeReminderController::class, 'index'])->name('api.v1.me.practice-reminders.index');
            Route::post('/', [PracticeReminderController::class, 'store'])->name('api.v1.me.practice-reminders.store');
            Route::get('/{practice_reminder}', [PracticeReminderController::class, 'show'])->name('api.v1.me.practice-reminders.show');
            Route::match(['put', 'patch'], '/{practice_reminder}', [PracticeReminderController::class, 'update'])->name('api.v1.me.practice-reminders.update');
            Route::delete('/{practice_reminder}', [PracticeReminderController::class, 'destroy'])->name('api.v1.me.practice-reminders.destroy');
            Route::post('/{practice_reminder}/pause', [PracticeReminderController::class, 'pause'])->name('api.v1.me.practice-reminders.pause');
            Route::post('/{practice_reminder}/resume', [PracticeReminderController::class, 'resume'])->name('api.v1.me.practice-reminders.resume');
        });

        // Practice Reminder Settings & Quiet Hours Routes (Task 36)
        Route::prefix('me/practice-reminder-settings')->group(function (): void {
            Route::get('/', [PracticeReminderSettingsController::class, 'show'])->name('api.v1.me.practice-reminder-settings.show');
            Route::match(['put', 'patch'], '/', [PracticeReminderSettingsController::class, 'update'])->name('api.v1.me.practice-reminder-settings.update');
        });

        // Personal Practice Planner Routes (Task 37)
        Route::prefix('me/practice-plans')->group(function (): void {
            Route::get('/', [PracticePlanController::class, 'index'])->name('api.v1.me.practice-plans.index');
            Route::post('/', [PracticePlanController::class, 'store'])->name('api.v1.me.practice-plans.store');
            Route::get('/{practice_plan}', [PracticePlanController::class, 'show'])->name('api.v1.me.practice-plans.show');
            Route::match(['put', 'patch'], '/{practice_plan}', [PracticePlanController::class, 'update'])->name('api.v1.me.practice-plans.update');
            Route::delete('/{practice_plan}', [PracticePlanController::class, 'destroy'])->name('api.v1.me.practice-plans.destroy');
            Route::post('/{practice_plan}/pause', [PracticePlanController::class, 'pause'])->name('api.v1.me.practice-plans.pause');
            Route::post('/{practice_plan}/resume', [PracticePlanController::class, 'resume'])->name('api.v1.me.practice-plans.resume');
            Route::post('/{practice_plan}/archive', [PracticePlanController::class, 'archive'])->name('api.v1.me.practice-plans.archive');

            // Plan Items
            Route::get('/{practice_plan}/items', [PracticePlanItemController::class, 'index'])->name('api.v1.me.practice-plans.items.index');
            Route::post('/{practice_plan}/items', [PracticePlanItemController::class, 'store'])->name('api.v1.me.practice-plans.items.store');
        });

        Route::prefix('me/practice-plan-items')->group(function (): void {
            Route::get('/{practice_plan_item}', [PracticePlanItemController::class, 'show'])->name('api.v1.me.practice-plan-items.show');
            Route::match(['put', 'patch'], '/{practice_plan_item}', [PracticePlanItemController::class, 'update'])->name('api.v1.me.practice-plan-items.update');
            Route::delete('/{practice_plan_item}', [PracticePlanItemController::class, 'destroy'])->name('api.v1.me.practice-plan-items.destroy');
        });

        Route::prefix('me/practice-plan-occurrences')->group(function (): void {
            Route::get('/', [PracticePlanOccurrenceController::class, 'index'])->name('api.v1.me.practice-plan-occurrences.index');
            Route::post('/link-session', [PracticePlanOccurrenceController::class, 'linkSession'])->name('api.v1.me.practice-plan-occurrences.link-session');
            Route::delete('/unlink-session', [PracticePlanOccurrenceController::class, 'unlinkSession'])->name('api.v1.me.practice-plan-occurrences.unlink-session');
            Route::get('/candidate-sessions', [PracticePlanOccurrenceController::class, 'candidateSessions'])->name('api.v1.me.practice-plan-occurrences.candidate-sessions');
            Route::post('/skip', [PracticePlanOccurrenceController::class, 'skip'])->name('api.v1.me.practice-plan-occurrences.skip');
            Route::post('/restore', [PracticePlanOccurrenceController::class, 'restore'])->name('api.v1.me.practice-plan-occurrences.restore');
        });

        // Convenience endpoints
        Route::get('me/practice-planner/today', [PracticePlanOccurrenceController::class, 'today'])->name('api.v1.me.practice-planner.today');
        Route::get('me/practice-planner/week', [PracticePlanOccurrenceController::class, 'week'])->name('api.v1.me.practice-planner.week');
    });
});
