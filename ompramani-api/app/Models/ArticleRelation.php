<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArticleRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'related_type',
        'related_id',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'related_id' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo(null, 'related_type', 'related_id');
    }
}
