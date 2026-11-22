<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudinaryMediaMapping extends Model
{
    use HasFactory;

    protected $table = 'cloudinary_media_mappings';

    protected $fillable = [
        'local_path',
        'public_id',
        'resource_type',
        'format',
        'secure_url',
        'checksum',
        'file_size',
        'status',
        'error_message',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];
}
