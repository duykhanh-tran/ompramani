<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\VideoCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VideoSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::in(array_map(fn ($c) => $c->value, VideoCategory::cases()))],
            'topic' => ['nullable', 'string', 'max:100'],
            'speaker' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string', Rule::in(['newest', 'oldest', 'duration_desc', 'duration_asc', 'relevance'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
