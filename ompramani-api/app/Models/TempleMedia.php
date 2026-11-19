<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TempleMedia extends Model
{
    use HasFactory;

    protected $table = 'temple_media';

    protected $fillable = [
        'temple_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'alt_text',
        'caption',
        'credit',
        'license_info',
        'sort_order',
        'is_cover',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
    ];

    public function temple(): BelongsTo
    {
        return $this->belongsTo(Temple::class);
    }
}
