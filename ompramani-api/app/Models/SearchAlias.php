<?php

namespace App\Models;

use App\Services\Search\SearchNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SearchAlias extends Model
{
    use HasFactory;

    protected $fillable = [
        'searchable_type',
        'searchable_id',
        'alias',
        'normalized_alias',
    ];

    protected static function booted(): void
    {
        static::saving(function (SearchAlias $model): void {
            if (empty($model->normalized_alias) && ! empty($model->alias)) {
                $model->normalized_alias = SearchNormalizer::normalize($model->alias);
            }
        });
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }
}
