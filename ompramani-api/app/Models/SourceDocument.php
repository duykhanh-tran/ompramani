<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size',
        'checksum',
        'source_name',
        'translator',
        'publisher',
        'notes',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function documentImports(): HasMany
    {
        return $this->hasMany(DocumentImport::class);
    }

    public function sutraVersions(): HasMany
    {
        return $this->hasMany(SutraVersion::class);
    }
}
