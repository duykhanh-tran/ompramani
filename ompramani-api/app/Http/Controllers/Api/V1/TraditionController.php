<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TraditionResource;
use App\Models\Tradition;
use Illuminate\Http\JsonResponse;

class TraditionController extends Controller
{
    /**
     * Display a listing of active traditions.
     */
    public function index(): JsonResponse
    {
        $traditions = Tradition::query()
            ->active()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'data' => TraditionResource::collection($traditions),
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
