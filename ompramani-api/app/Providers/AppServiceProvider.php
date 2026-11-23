<?php

namespace App\Providers;

use App\Events\Prayer\PrayerIntentionApproved;
use App\Events\Prayer\PrayerIntentionRejected;
use App\Events\Prayer\PrayerIntentionRevoked;
use App\Events\Prayer\PrayerResponseApproved;
use App\Events\Prayer\PrayerResponseRejected;
use App\Events\Prayer\PrayerResponseRevoked;
use App\Listeners\Prayer\SendPrayerModerationNotification;
use App\Models\Article;
use App\Models\BuddhistFigure;
use App\Models\BuddhistVideo;
use App\Models\Mantra;
use App\Models\Sutra;
use App\Services\Search\SearchNormalizer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') || str_starts_with((string) config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // Register custom Cloudinary Filesystem Driver with auto-mapping & double-extension prevention
        Storage::extend('cloudinary', function ($app, array $config) {
            $cloudName = $config['cloud_name'] ?? env('CLOUDINARY_CLOUD_NAME');
            $apiKey = $config['api_key'] ?? env('CLOUDINARY_API_KEY');
            $apiSecret = $config['api_secret'] ?? env('CLOUDINARY_API_SECRET');
            $url = $config['url'] ?? env('CLOUDINARY_URL');

            $cloudinaryConfig = [];
            if (filled($url) && is_string($url)) {
                $cloudinaryConfig = $url;
            } else {
                $cloudinaryConfig = [
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                    ],
                    'url' => [
                        'secure' => (bool) ($config['url']['secure'] ?? env('CLOUDINARY_SECURE_URL', true)),
                    ],
                ];
            }

            $cloudinary = new \Cloudinary\Cloudinary($cloudinaryConfig);
            $diskOptions = \CodebarAg\FlysystemCloudinary\CloudinaryDiskOptions::fromDiskAndConfig($config);
            $adapter = new \App\Services\Media\CloudinaryStorageAdapter($cloudinary, $diskOptions);

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new \League\Flysystem\Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });

        Relation::morphMap([
            'sutra' => Sutra::class,
            'figure' => BuddhistFigure::class,
            'mantra' => Mantra::class,
            'article' => Article::class,
            'video' => BuddhistVideo::class,
        ]);

        RateLimiter::for('api', function (Request $request): Limit {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }

            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            return config('app.frontend_url', 'http://localhost:3000').'/dat-lai-mat-khau?token='.$token.'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());
        });

        Event::listen([
            PrayerIntentionApproved::class,
            PrayerIntentionRejected::class,
            PrayerIntentionRevoked::class,
            PrayerResponseApproved::class,
            PrayerResponseRejected::class,
            PrayerResponseRevoked::class,
        ], SendPrayerModerationNotification::class);

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() === 'sqlite') {
                $event->connection->getPdo()->sqliteCreateFunction('unaccent', function (?string $text): ?string {
                    return $text !== null ? SearchNormalizer::removeAccents($text) : null;
                });
            }
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            try {
                DB::connection()->getPdo()->sqliteCreateFunction('unaccent', function (?string $text): ?string {
                    return $text !== null ? SearchNormalizer::removeAccents($text) : null;
                });
            } catch (\Throwable) {
                // PDO might not be initialized yet
            }
        }
    }
}
