<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ArticleCategoryResource;
use App\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of active article categories.
     */
    public function index(): JsonResponse
    {
        $categories = ArticleCategory::query()
            ->active()
            ->withCount('publishedArticles')
            ->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => ArticleCategoryResource::collection($categories),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
