<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PokemonController extends Controller
{
    /**
     * Get all pokemons paginated
     */
    public function index(Request $request): JsonResponse
    {
        // Validate with custom error response
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'sort' => 'nullable|string|in:name-asc,name-desc,id-asc,id-desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'error_message' => $validator->errors()->first(),
            ], 422);
        }

        // Get parameters with defaults
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);
        $sort = $request->get('sort', 'id-asc');

        $query = Pokemon::with(['types' => function ($q) {
            $q->orderBy('slot');
        }]);

        // Apply sorting
        switch ($sort) {
            case 'name-asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name-desc':
                $query->orderBy('name', 'desc');
                break;
            case 'id-asc':
                $query->orderBy('external_id', 'asc');
                break;
            case 'id-desc':
                $query->orderBy('external_id', 'desc');
                break;
            default:
                $query->orderBy('external_id', 'asc');
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination
        $pokemons = $query->skip($offset)
            ->take($limit)
            ->get();

        // Transform data
        $data = $pokemons->map(function ($pokemon) {
            $sprites = $pokemon->sprites ?? [];
            
            return [
                'id' => $pokemon->external_id,
                'name' => $pokemon->name,
                'sprites' => [
                    'front_default' => $sprites['front_default'] ?? null,
                ],
                'types' => $pokemon->types->map(function ($type) {
                    return [
                        'type' => [
                            'name' => $type->name,
                        ],
                        'slot' => $type->pivot->slot,
                    ];
                })->values()->toArray(),
            ];
        });

        // Calculate pagination metadata
        $currentPage = $offset > 0 ? floor($offset / $limit) + 1 : 1;
        $pages = $limit > 0 ? (int) ceil($total / $limit) : 0;
        
        // Build next/previous URLs
        $baseUrl = url('/api/v2/pokemons');
        
        // Get all query parameters except offset
        $queryParams = $request->except(['offset']);
        $queryParams['limit'] = $limit;

        $nextOffset = $offset + $limit;
        $nextUrl = $nextOffset < $total 
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['offset' => $nextOffset]))
            : null;

        $prevOffset = $offset - $limit;
        $prevUrl = $prevOffset >= 0
            ? $baseUrl . '?' . http_build_query(array_merge($queryParams, ['offset' => $prevOffset]))
            : null;

        return response()->json([
            'data' => $data,
            'metadata' => [
                'next' => $nextUrl,
                'previous' => $prevUrl,
                'total' => $total,
                'pages' => $pages,
                'page' => $currentPage,
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}