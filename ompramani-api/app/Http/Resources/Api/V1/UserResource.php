<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified' => $this->hasVerifiedEmail(),
            'profile' => $this->profile ? new UserProfileResource($this->profile) : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    }
}
