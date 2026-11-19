<?php

namespace App\Models;

use App\Enums\DocumentImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_document_id',
        'sutra_id',
        'sutra_version_id',
        'version_name',
        'translator',
        'language',
        'publisher',
        'source',
        'notes',
        'status',
        'total_sections',
        'processed_sections',
        'error_message',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'status' => DocumentImportStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_sections' => 'integer',
        'processed_sections' => 'integer',
    ];

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class);
    }

    public function sutra(): BelongsTo
    {
        return $this->belongsTo(Sutra::class);
    }

    public function sutraVersion(): BelongsTo
    {
        return $this->belongsTo(SutraVersion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ImportSection::class)->orderBy('position');
    }
}
