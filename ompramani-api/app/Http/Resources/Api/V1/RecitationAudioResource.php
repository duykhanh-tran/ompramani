<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RecitationAudio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecitationAudio
 */
class RecitationAudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_url' => $this->resolveFileUrl(),
            'mime_type' => $this->mime_type,
            'duration_seconds' => $this->duration_seconds,
            'performer' => $this->performer,
            'source' => $this->source,
            'license_information' => $this->license_information,
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
