<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TempleSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempleSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'temple_id',
        'source_title',
        'source_url',
        'source_type',
        'reference_note',
        'verified_fields',
        'checked_at',
    ];

    protected $casts = [
        'source_type' => TempleSourceType::class,
        'verified_fields' => 'array',
        'checked_at' => 'datetime',
    ];

    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class);
    }
}
