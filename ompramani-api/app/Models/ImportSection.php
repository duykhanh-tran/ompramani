<?php

namespace App\Models;

use App\Enums\ImportSectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_import_id',
        'parent_id',
        'type',
        'title',
        'content',
        'position',
        'source_index',
        'heading_level',
        'source_page_start',
        'source_page_end',
        'is_included',
    ];

    protected $casts = [
        'type' => ImportSectionType::class,
        'position' => 'integer',
        'source_index' => 'integer',
        'heading_level' => 'integer',
        'source_page_start' => 'integer',
        'source_page_end' => 'integer',
        'is_included' => 'boolean',
    ];

    public function documentImport(): BelongsTo
    {
        return $this->belongsTo(DocumentImport::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }
}
