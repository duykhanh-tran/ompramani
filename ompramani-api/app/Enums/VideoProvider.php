<?php

declare(strict_types=1);

namespace App\Enums;

enum VideoProvider: string
{
    case YouTube = 'youtube';
    case Vimeo = 'vimeo';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Vimeo => 'Vimeo',
            self::External => 'Nguồn Ngoài / Khác',
        };
    }

    public function isValidVideoId(?string $videoId): bool
    {
        if (empty($videoId)) {
            return false;
        }

        return match ($this) {
            self::YouTube => (bool) preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId),
            self::Vimeo => (bool) preg_match('/^[0-9]{6,12}$/', $videoId),
            self::External => false,
        };
    }

    public function buildEmbedUrl(?string $videoId): ?string
    {
        if (! $this->isValidVideoId($videoId)) {
            return null;
        }

        return match ($this) {
            self::YouTube => "https://www.youtube-nocookie.com/embed/{$videoId}",
            self::Vimeo => "https://player.vimeo.com/video/{$videoId}",
            self::External => null,
        };
    }

    public function extractVideoId(string $url): ?string
    {
        $url = trim($url);

        if ($this === self::YouTube) {
            // Match youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID, youtube-nocookie.com/embed/ID, youtube.com/shorts/ID
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $url, $match)) {
                return $match[1];
            }
        } elseif ($this === self::Vimeo) {
            // Match vimeo.com/12345678 or player.vimeo.com/video/12345678
            if (preg_match('%(?:vimeo\.com/(?:channels/(?:\w+/)?|groups/[^/]+/videos/|album/(?:\d+/)?video/|video/|)(\d+))%i', $url, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * Auto-detect supported video provider from given URL.
     */
    public static function detectFromUrl(string $url): ?self
    {
        $url = trim($url);

        if (preg_match('#^(?:https?://)?(?:(?:www|m)\.)?(?:youtube(?:-nocookie)?\.com|youtu\.be)/#i', $url)) {
            return self::YouTube;
        }

        if (preg_match('#^(?:https?://)?(?:(?:www|player)\.)?vimeo\.com/#i', $url)) {
            return self::Vimeo;
        }

        return null;
    }
}
