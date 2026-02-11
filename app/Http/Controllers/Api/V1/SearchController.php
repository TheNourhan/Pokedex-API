<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchController extends Controller
{
    /**
     * Search for pokemons by name or type
     */
    public function search(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'query' => 'required|string|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'error_message' => $validator->errors()->first(),
            ], 422);
        }

        $query = trim($request->get('query'));
        $limit = $request->get('limit', 20);

        // Search by name OR by type
        $pokemons = Pokemon::with(['types' => function ($q) {
                $q->orderBy('slot');
            }])
            ->where('name', 'LIKE', '%' . $query . '%')
            ->orWhereHas('types', function ($typeQuery) use ($query) {
                $typeQuery->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('external_id')
            ->limit($limit)
            ->get();

        // Transform to match Pokemon schema
        $results = $pokemons->map(function ($pokemon) {
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

        return response()->json($results, 200, [], JSON_UNESCAPED_SLASHES);
    }
}
