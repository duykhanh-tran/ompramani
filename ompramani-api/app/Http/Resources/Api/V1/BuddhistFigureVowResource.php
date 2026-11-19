<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BuddhistFigureVow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BuddhistFigureVow
 */
class BuddhistFigureVowResource extends JsonResource
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
            'buddhist_figure_id' => $this->buddhist_figure_id,
            'title' => $this->title,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
        ];
    }
}
