<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrayerResponseAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'prayer_response_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'reason_code',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function prayerResponse(): BelongsTo
    {
        return $this->belongsTo(PrayerResponse::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
