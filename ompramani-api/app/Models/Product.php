<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryMode;
use App\Enums\ProductStatus;
use App\Services\Search\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    /**
     * Forbidden commercialized / superstitious claim phrases in Buddhist products.
     */
    public const FORBIDDEN_CLAIMS = [
        'tăng công đức',
        'chữa bệnh',
        'giải nghiệp',
        'may mắn tuyệt đối',
        'hộ thân linh ứng 100%',
        'phù hộ trúng số',
        'trừ tà tuyệt đối',
    ];

    protected $fillable = [
        'public_id',
        'category_id',
        'name',
        'normalized_name',
        'slug',
        'sku',
        'short_description',
        'description',
        'price_vnd',
        'stock_quantity',
        'inventory_mode',
        'status',
        'is_active',
        'cover_image',
        'published_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'price_vnd' => 'integer',
        'stock_quantity' => 'integer',
        'inventory_mode' => InventoryMode::class,
        'status' => ProductStatus::class,
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            if (empty($product->public_id)) {
                $product->public_id = (string) Str::ulid();
            }

            if (empty($product->slug) && ! empty($product->name)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }

            if ($product->status === ProductStatus::Published && is_null($product->published_at)) {
                $product->published_at = now();
            }
        });

        static::saving(function (Product $product): void {
            $product->validateBusinessRules();
            $product->normalized_name = SearchNormalizer::normalize((string) $product->name);
        });
    }

    public function validateBusinessRules(): void
    {
        if ($this->price_vnd !== null && $this->price_vnd < 0) {
            throw new InvalidArgumentException('Giá vật phẩm không thể là số âm.');
        }

        if ($this->stock_quantity < 0) {
            throw new InvalidArgumentException('Số lượng tồn kho không thể là số âm.');
        }

        // Validate forbidden superstitious claims in name or descriptions
        $fullContent = strtolower(implode(' ', array_filter([
            $this->name,
            $this->short_description,
            $this->description,
        ])));

        foreach (self::FORBIDDEN_CLAIMS as $claim) {
            if (str_contains($fullContent, strtolower($claim))) {
                throw new InvalidArgumentException("Nội dung chứa cụm từ mê tín/thương mại hóa tâm linh không phù hợp triết lý Phật giáo: '{$claim}'.");
            }
        }
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if (empty($base)) {
            $base = 'vat-pham';
        }

        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id')->orderBy('created_at', 'desc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeByCategory(Builder $query, string|int $category): Builder
    {
        if (is_numeric($category)) {
            return $query->where('category_id', (int) $category);
        }

        return $query->whereHas('category', function (Builder $q) use ($category) {
            $q->where('slug', $category);
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $normalized = SearchNormalizer::normalize($term);
        $rawTerm = '%'.strtolower(trim($term)).'%';
        $normTerm = '%'.$normalized.'%';

        return $query->where(function (Builder $q) use ($rawTerm, $normTerm) {
            $q->where('normalized_name', 'like', $normTerm)
                ->orWhere('name', 'like', $rawTerm)
                ->orWhere('sku', 'like', $rawTerm);
        });
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active || $this->status !== ProductStatus::Published) {
            return false;
        }

        if ($this->inventory_mode === InventoryMode::Tracked) {
            return $this->stock_quantity > 0;
        }

        return true;
    }

    public function formattedPrice(): ?string
    {
        if ($this->price_vnd === null) {
            return null;
        }

        return number_format($this->price_vnd, 0, ',', '.').' ₫';
    }
}
