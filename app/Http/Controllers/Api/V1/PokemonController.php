<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pokemon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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

    /**
     * Get a pokemon by id
     */
    public function show($id): JsonResponse
    {
        // Find by external_id (not the internal database id)
        $pokemon = Pokemon::where('external_id', $id)
            ->with([
                'types' => function ($query) {
                    $query->orderBy('slot');
                },
                'abilities' => function ($query) {
                    $query->orderBy('slot');
                },
                'stats',
                'moves',
            ])
            ->first();

        if (!$pokemon) {
            return response()->json([
                'error' => 'Not Found',
                'error_message' => 'Pokemon not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $sprites = $pokemon->sprites ?? [];

        $data = [
            'id' => $pokemon->external_id,
            'name' => $pokemon->name,
            'sprites' => [
                'front_default' => $sprites['front_default'] ?? null,
                'front_female' => $sprites['front_female'] ?? null,
                'front_shiny' => $sprites['front_shiny'] ?? null,
                'front_shiny_female' => $sprites['front_shiny_female'] ?? null,
                'back_default' => $sprites['back_default'] ?? null,
                'back_female' => $sprites['back_female'] ?? null,
                'back_shiny' => $sprites['back_shiny'] ?? null,
                'back_shiny_female' => $sprites['back_shiny_female'] ?? null,
            ],
            'types' => $pokemon->types->map(function ($type) {
                return [
                    'type' => $type->name,
                    'slot' => $type->pivot->slot,
                ];
            })->toArray(),
            'height' => $pokemon->height,
            'weight' => $pokemon->weight,
            'moves' => $pokemon->moves->map(function ($move) {
                return [
                    'move' => $move->name,
                    'version_group_details' => $move->pivot->version_group_details ?? [],
                ];
            })->toArray(),
            'order' => $pokemon->order,
            'species' => $pokemon->species,
            'stats' => $pokemon->stats->map(function ($stat) {
                return [
                    'stat' => $stat->name,
                    'base_stat' => $stat->pivot->base_stat,
                    'effort' => $stat->pivot->effort,
                ];
            })->toArray(),
            'abilities' => $pokemon->abilities->map(function ($ability) {
                return [
                    'ability' => $ability->name,
                    'is_hidden' => (bool) $ability->pivot->is_hidden,
                    'slot' => $ability->pivot->slot,
                ];
            })->toArray(),
            'form' => $pokemon->form,
        ];

        return response()->json($data, 200, [], JSON_UNESCAPED_SLASHES);
    }
}