<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Media\MediaUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SutraReadingProgressResource extends JsonResource
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
            'sutra' => $this->sutra ? [
                'id' => $this->sutra->id,
                'title' => $this->sutra->title,
                'slug' => $this->sutra->slug,
                'thumbnail' => MediaUrlResolver::resolve($this->sutra->thumbnail),
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
                'language' => $this->version->language,
            ] : null,
            'section' => $this->section ? [
                'id' => $this->section->id,
                'title' => $this->section->title,
                'slug' => $this->section->slug,
                'position' => $this->section->position,
                'source_page_start' => $this->section->source_page_start,
                'source_page_end' => $this->section->source_page_end,
            ] : null,
            'last_read_at' => $this->last_read_at?->toIso8601String(),
            'revision' => $this->revision,
            'continue_url' => $this->sutra && $this->section
                ? ($this->version
                    ? "/kinh/{$this->sutra->slug}?version={$this->version->id}&section={$this->section->id}"
                    : "/kinh/{$this->sutra->slug}?section={$this->section->id}")
                : ($this->sutra ? "/kinh/{$this->sutra->slug}" : null),
        ];
    }
}
