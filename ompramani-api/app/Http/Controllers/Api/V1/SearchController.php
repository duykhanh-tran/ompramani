<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Http\Requests\Api\V1\SearchSuggestionsRequest;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search endpoint for published sutras and features.
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $result = $this->searchService->search($request->validated(), $request->user());

        return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Fast suggestions / autocomplete endpoint.
     */
    public function suggestions(SearchSuggestionsRequest $request): JsonResponse
    {
        $query = (string) $request->input('q');
        $limit = (int) $request->input('limit', 10);

        $suggestions = $this->searchService->suggest($query, $limit);

        return response()->json([
            'data' => $suggestions,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
