<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQueryLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'query',
        'normalized_query',
        'result_count',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'result_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
