<?php

namespace App\Http\Resources\Api\V1;

use App\Models\MantraVersion;
use App\Models\PracticeSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PracticeSession
 */
class PracticeSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subjectModel = $this->getSubjectModel();
        $subjectData = null;

        if ($subjectModel) {
            $title = $subjectModel->title ?? $subjectModel->name ?? null;
            if ($subjectModel instanceof MantraVersion && $subjectModel->mantra) {
                $title = $subjectModel->mantra->title.' ('.$subjectModel->name.')';
            }
            $slug = $subjectModel->slug ?? ($subjectModel instanceof MantraVersion ? $subjectModel->mantra?->slug : null);

            $subjectData = [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'title' => $title,
                'slug' => $slug,
            ];
        }

        return [
            'id' => $this->id,
            'practice_type' => $this->practice_type->value,
            'practice_type_label' => $this->practice_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'subject' => $subjectData,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'active_duration_seconds' => $this->active_duration_seconds,
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'progress_data' => $this->progress_data ?? [],
            'derived_metrics' => $this->deriveMetrics(),
            'client_session_key' => $this->client_session_key,
            'sequence' => $this->sequence,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
