<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PokemonController extends Controller
{
    /**
     * Get all pokemons
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pokemon::with(['types' => function ($query) {
            $query->orderBy('slot');
        }]);

        // Apply sorting based on query parameter
        $sort = $request->get('sort', 'id-asc');
        
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
        }

        // Get all pokemons (for v1 endpoint, no pagination)
        $pokemons = $query->get();

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
                })->toArray(),
            ];
        });

        return response()->json($data, 200, [], JSON_UNESCAPED_SLASHES);
    }
}