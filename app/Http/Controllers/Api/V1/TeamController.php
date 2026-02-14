<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamPokemonsRequest;
use App\Models\Pokemon;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * Get all teams
     */
    public function index(): JsonResponse
    {
        $teams = Team::with('pokemons')->get();
        
        return response()->json(
            $teams->map(fn($team) => $this->transformTeam($team))
        );
    }

    /**
     * Create a new team
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create($request->validated());
        
        return response()->json(
            $this->transformTeam($team),
            201
        );
    }

    /**
     * Get a team by id
     */
    public function show(Team $team): JsonResponse
    {
        $team->load('pokemons');
        
        return response()->json($this->transformTeam($team));
    }

    /**
     * Set pokemons of a team
     */
    public function setPokemons(UpdateTeamPokemonsRequest $request, Team $team): JsonResponse
    {
        $pokemonIds = Pokemon::whereIn('external_id', $request->pokemons)
            ->pluck('id')
            ->toArray();

        // Sync pokemons (this will remove old ones and add new ones)
        $team->pokemons()->sync($pokemonIds);
        
        // Reload relationship
        $team->load('pokemons');

        return response()->json($this->transformTeam($team));
    }

    private function transformTeam(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'pokemons' => $team->pokemons->pluck('external_id')->toArray(),
            // 'created_at' => $team->created_at,
            // 'updated_at' => $team->updated_at,
        ];
    }
}