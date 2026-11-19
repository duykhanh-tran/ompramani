<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SutraBookmarkResource extends JsonResource
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
            'type' => $this->type,
            'sutra' => $this->sutra ? [
                'id' => $this->sutra->id,
                'title' => $this->sutra->title,
                'slug' => $this->sutra->slug,
                'thumbnail' => $this->sutra->thumbnail,
                'translator' => $this->sutra->translator,
                'tradition' => $this->sutra->tradition ? [
                    'id' => $this->sutra->tradition->id,
                    'name' => $this->sutra->tradition->name,
                ] : null,
            ] : null,
            'version' => $this->version ? [
                'id' => $this->version->id,
                'name' => $this->version->name,
                'translator' => $this->version->translator,
            ] : null,
            'section' => $this->section ? [
                'id' => $this->section->id,
                'title' => $this->section->title,
                'slug' => $this->section->slug,
                'position' => $this->section->position,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'target_url' => $this->type === 'section' && $this->sutra && $this->section
                ? ($this->version
                    ? "/kinh/{$this->sutra->slug}?version={$this->version->id}&section={$this->section->id}"
                    : "/kinh/{$this->sutra->slug}?section={$this->section->id}")
                : ($this->sutra ? "/kinh/{$this->sutra->slug}" : null),
        ];
    }
}
