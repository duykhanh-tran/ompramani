<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuddhistVideos\Schemas;

use App\Enums\VideoProvider;
use App\Models\BuddhistVideo;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BuddhistVideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->label('Tiêu đề video')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, callable $set, callable $get): void {
                        if ($operation === 'create' && filled($state) && empty($get('slug'))) {
                            $set('slug', BuddhistVideo::generateUniqueSlug((string) $state));
                        }
                    }),

                TextInput::make('slug')
                    ->label('Đường dẫn (Slug)')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                    ->helperText('Định danh URL duy nhất. Tự động gợi ý từ tiêu đề.'),

                TextInput::make('canonical_source_url')
                    ->label('Liên kết video (URL)')
                    ->required()
                    ->url()
                    ->maxLength(1000)
                    ->helperText('Dán liên kết video hợp lệ từ YouTube hoặc Vimeo.')
                    ->rules([
                        function () {
                            return function (string $attribute, mixed $value, \Closure $fail): void {
                                if (empty($value)) {
                                    return;
                                }

                                $url = trim((string) $value);

                                // Security: Only allow https:// or http://
                                if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
                                    $fail('Liên kết video phải bắt đầu bằng https://');

                                    return;
                                }

                                // Security: Disallow localhost, private IPs
                                $host = parse_url($url, PHP_URL_HOST);
                                if (! $host || in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true) || preg_match('/^10\.|^192\.168\.|^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host)) {
                                    $fail('Liên kết video không được trỏ đến địa chỉ nội bộ.');

                                    return;
                                }

                                // Provider detection
                                $provider = VideoProvider::detectFromUrl($url);
                                if (! $provider || $provider === VideoProvider::External) {
                                    $fail('Hệ thống hiện chỉ hỗ trợ liên kết video từ YouTube hoặc Vimeo.');

                                    return;
                                }

                                $videoId = $provider->extractVideoId($url);
                                if (! $videoId || ! $provider->isValidVideoId($videoId)) {
                                    $fail("Không thể nhận diện mã video hợp lệ từ đường dẫn {$provider->label()}. Vui lòng kiểm tra lại liên kết.");
                                }
                            };
                        },
                    ]),
            ]);
    }
}
