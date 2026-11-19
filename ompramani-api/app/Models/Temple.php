<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TempleCoordinateAccuracy;
use App\Enums\TemplePublicAccessStatus;
use App\Enums\TemplePublicationStatus;
use App\Enums\TempleType;
use App\Enums\TempleVerificationStatus;
use App\Services\Search\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Temple extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'public_id',
        'slug',
        'name',
        'alternative_names',
        'place_type',
        'short_description',
        'description',
        'cover_image',
        'construction_year',
        'opening_hours',
        'admission_fee',
        'highlights',
        'history',
        'architecture_culture',
        'address_line',
        'google_maps_url',
        'ward_name',
        'district_name',
        'province_name',
        'country_code',
        'postal_code',
        'latitude',
        'longitude',
        'coordinate_accuracy',
        'public_access_status',
        'visit_notes',
        'visit_notes_source_id',
        'visit_notes_verified_at',
        'public_phone',
        'public_email',
        'official_website',
        'verification_status',
        'publication_status',
        'reviewed_by',
        'reviewed_at',
        'published_at',
        'last_verified_at',
        'normalized_name',
        'normalized_alternative_names',
    ];

    protected $casts = [
        'alternative_names' => 'array',
        'place_type' => TempleType::class,
        'coordinate_accuracy' => TempleCoordinateAccuracy::class,
        'public_access_status' => TemplePublicAccessStatus::class,
        'verification_status' => TempleVerificationStatus::class,
        'publication_status' => TemplePublicationStatus::class,
        'admission_fee' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'visit_notes_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Temple $temple): void {
            if (empty($temple->public_id)) {
                $temple->public_id = (string) Str::ulid();
            }

            if (empty($temple->slug) && ! empty($temple->name)) {
                $temple->slug = static::generateUniqueSlug($temple->name, $temple->province_name);
            }

            if (empty($temple->place_type)) {
                $temple->place_type = TempleType::Temple;
            }

            if (empty($temple->publication_status)) {
                $temple->publication_status = TemplePublicationStatus::Draft;
            }

            if (empty($temple->country_code)) {
                $temple->country_code = 'VN';
            }
        });

        static::saving(function (Temple $temple): void {
            $temple->validateCoordinates();
            $temple->validateContacts();
            $temple->sanitizeInputs();
            $temple->updateNormalizedSearchKeys();
        });

        static::saved(function (Temple $temple): void {
            $temple->clearDetailCache();

            if (filled($temple->cover_image)) {
                $temple->media()->updateOrCreate(
                    ['is_cover' => true],
                    [
                        'file_path' => $temple->cover_image,
                        'file_name' => basename($temple->cover_image),
                        'sort_order' => 0,
                    ]
                );
            }
        });

        static::deleted(function (Temple $temple): void {
            $temple->clearDetailCache();
        });
    }

    public function clearDetailCache(): void
    {
        Cache::forget("temple_detail_{$this->slug}");
        Cache::forget("temple_detail_{$this->public_id}");
        Cache::forget('temple_filter_facets');
    }

    /**
     * Update normalized search keys for accent-insensitive search.
     */
    public function updateNormalizedSearchKeys(): void
    {
        $this->normalized_name = SearchNormalizer::normalize($this->name);

        $altText = '';
        if (! empty($this->alternative_names)) {
            $altNames = is_array($this->alternative_names) ? $this->alternative_names : [];
            $extracted = array_map(function ($item) {
                if (is_array($item)) {
                    return $item['name'] ?? '';
                }

                return (string) $item;
            }, $altNames);
            $altText = implode(' ', array_filter($extracted));
        }

        $this->normalized_alternative_names = SearchNormalizer::normalize($altText);
    }

    /**
     * Validate coordinate boundaries and pairing integrity.
     */
    public function validateCoordinates(): void
    {
        $hasLat = $this->latitude !== null && $this->latitude !== '';
        $hasLng = $this->longitude !== null && $this->longitude !== '';

        if (($hasLat && ! $hasLng) || (! $hasLat && $hasLng)) {
            throw new InvalidArgumentException('Tọa độ kinh độ và vĩ độ phải cùng có giá trị hoặc cùng để trống.');
        }

        if ($hasLat) {
            $lat = (float) $this->latitude;
            if ($lat < -90.0 || $lat > 90.0) {
                throw new InvalidArgumentException('Vĩ độ (latitude) phải nằm trong khoảng từ -90 đến 90 độ.');
            }
        }

        if ($hasLng) {
            $lng = (float) $this->longitude;
            if ($lng < -180.0 || $lng > 180.0) {
                throw new InvalidArgumentException('Kinh độ (longitude) phải nằm trong khoảng từ -180 đến 180 độ.');
            }
        }
    }

    /**
     * Validate public contacts format.
     */
    public function validateContacts(): void
    {
        if (filled($this->public_email) && ! filter_var($this->public_email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email công khai không đúng định dạng.');
        }

        if (filled($this->official_website)) {
            $url = (string) $this->official_website;
            if (! filter_var($url, FILTER_VALIDATE_URL) || (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://'))) {
                throw new InvalidArgumentException('Trang web chính thức phải là một URL hợp lệ (http:// hoặc https://).');
            }
        }
    }

    /**
     * Sanitize input strings to avoid XSS or unsafe HTML.
     */
    public function sanitizeInputs(): void
    {
        if (is_string($this->name)) {
            $this->name = strip_tags(trim($this->name));
        }
        if (is_string($this->short_description)) {
            $this->short_description = strip_tags(trim($this->short_description));
        }
        if (is_string($this->address_line)) {
            $this->address_line = strip_tags(trim($this->address_line));
        }
        if (is_string($this->ward_name)) {
            $this->ward_name = strip_tags(trim($this->ward_name));
        }
        if (is_string($this->district_name)) {
            $this->district_name = strip_tags(trim($this->district_name));
        }
        if (is_string($this->province_name)) {
            $this->province_name = strip_tags(trim($this->province_name));
        }
        if (is_string($this->public_phone)) {
            $this->public_phone = strip_tags(trim($this->public_phone));
        }
        if (is_string($this->visit_notes)) {
            $this->visit_notes = strip_tags(trim($this->visit_notes));
        }
        if (is_string($this->construction_year)) {
            $this->construction_year = strip_tags(trim($this->construction_year));
        }
        if (is_string($this->opening_hours)) {
            $this->opening_hours = strip_tags(trim($this->opening_hours));
        }
    }

    /**
     * Generate unique slug disambiguated by province or numeric sequence.
     */
    public static function generateUniqueSlug(string $name, ?string $province = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if (empty($base)) {
            $base = 'chua';
        }

        $candidate = $base;
        $counter = 1;

        while (static::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists()) {
            if ($counter === 1 && filled($province)) {
                $candidate = $base.'-'.Str::slug($province);
            } else {
                $candidate = $base.'-'.$counter;
            }
            $counter++;
        }

        return $candidate;
    }

    /**
     * Formatted human-readable address.
     */
    public function formattedAddress(): string
    {
        $parts = array_filter([
            $this->address_line,
            $this->ward_name,
            $this->district_name,
            $this->province_name,
            $this->country_code === 'VN' ? 'Việt Nam' : $this->country_code,
        ], fn ($part) => filled($part));

        return implode(', ', $parts);
    }

    /**
     * Check if coordinates can be exposed publicly.
     */
    public function hasPublicCoordinates(): bool
    {
        $acc = $this->coordinate_accuracy instanceof TempleCoordinateAccuracy
            ? $this->coordinate_accuracy
            : ($this->coordinate_accuracy ? TempleCoordinateAccuracy::tryFrom($this->coordinate_accuracy) : null);

        $ver = $this->verification_status instanceof TempleVerificationStatus
            ? $this->verification_status
            : ($this->verification_status ? TempleVerificationStatus::tryFrom($this->verification_status) : null);

        $accStatus = $this->public_access_status instanceof TemplePublicAccessStatus
            ? $this->public_access_status
            : ($this->public_access_status ? TemplePublicAccessStatus::tryFrom($this->public_access_status) : null);

        return $this->latitude !== null
            && $this->longitude !== null
            && $ver === TempleVerificationStatus::Verified
            && $acc === TempleCoordinateAccuracy::ExactVerified
            && $accStatus !== TemplePublicAccessStatus::Restricted;
    }

    public function getCoverImageUrl(): ?string
    {
        return $this->cover_image
            ?? $this->coverMedia?->file_path
            ?? $this->media()->where('is_cover', true)->value('file_path')
            ?? $this->media()->orderBy('sort_order')->value('file_path');
    }

    /* -------------------------------------------------------------------------
     * Relationships
     * ------------------------------------------------------------------------- */

    public function traditions(): BelongsToMany
    {
        return $this->belongsToMany(Tradition::class, 'temple_traditions')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function sources(): HasMany
    {
        return $this->hasMany(TempleSource::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(TempleMedia::class)->orderBy('sort_order');
    }

    public function coverMedia(): HasOne
    {
        return $this->hasOne(TempleMedia::class)->where('is_cover', true);
    }

    public function visitNotesSource(): BelongsTo
    {
        return $this->belongsTo(TempleSource::class, 'visit_notes_source_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* -------------------------------------------------------------------------
     * Scopes
     * ------------------------------------------------------------------------- */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', TemplePublicationStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', TempleVerificationStatus::Verified->value);
    }

    public function scopeByPlaceType(Builder $query, string|TempleType $type): Builder
    {
        $val = $type instanceof TempleType ? $type->value : $type;

        return $query->where('place_type', $val);
    }

    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country_code', strtoupper(trim($country)));
    }

    public function scopeByProvince(Builder $query, string $province): Builder
    {
        $province = trim($province);
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            return $query->where(function (Builder $q) use ($province): void {
                $q->where('province_name', 'ILIKE', "%{$province}%")
                    ->orWhere('address_line', 'ILIKE', "%{$province}%");
            });
        }

        return $query->where(function (Builder $q) use ($province): void {
            $q->where('province_name', 'LIKE', "%{$province}%")
                ->orWhere('address_line', 'LIKE', "%{$province}%")
                ->orWhereRaw('LOWER(province_name) LIKE ?', ['%'.mb_strtolower($province, 'UTF-8').'%'])
                ->orWhereRaw('LOWER(address_line) LIKE ?', ['%'.mb_strtolower($province, 'UTF-8').'%']);
        });
    }

    public function scopeByTradition(Builder $query, int|string $tradition): Builder
    {
        return $query->whereHas('traditions', function (Builder $q) use ($tradition): void {
            if (is_numeric($tradition)) {
                $q->where('traditions.id', (int) $tradition);
            } else {
                $q->where('traditions.slug', $tradition);
            }
        });
    }

    /**
     * Accent-insensitive search on normalized name and alternative names.
     */
    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        if ($q === null || trim($q) === '') {
            return $query;
        }

        $norm = SearchNormalizer::normalize($q);
        if ($norm === '') {
            return $query;
        }

        $escaped = addcslashes($norm, '%_\\');

        return $query->where(function (Builder $sub) use ($escaped): void {
            $sub->where('normalized_name', 'LIKE', "%{$escaped}%")
                ->orWhere('normalized_alternative_names', 'LIKE', "%{$escaped}%");
        });
    }

    /**
     * Order by search relevance when query is present.
     */
    public function scopeOrderByRelevance(Builder $query, ?string $q): Builder
    {
        if ($q === null || trim($q) === '') {
            return $query->orderBy('name', 'asc')->orderBy('id', 'asc');
        }

        $norm = SearchNormalizer::normalize($q);
        if ($norm === '') {
            return $query->orderBy('name', 'asc')->orderBy('id', 'asc');
        }

        $escaped = addcslashes($norm, '%_\\');

        return $query->orderByRaw(
            'CASE
                WHEN normalized_name = ? THEN 100
                WHEN normalized_name LIKE ? THEN 80
                WHEN normalized_name LIKE ? THEN 50
                WHEN normalized_alternative_names LIKE ? THEN 30
                ELSE 10
            END DESC',
            [$norm, "{$escaped}%", "%{$escaped}%", "%{$escaped}%"]
        )->orderBy('name', 'asc')->orderBy('id', 'asc');
    }
}
