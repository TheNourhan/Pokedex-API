<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Relationships\PokemonMovePivot;

class Move extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    /**
     * Get the pokemons that can learn this move
     */
    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_moves')
            ->withPivot('version_group_details')
            ->using(PokemonMovePivot::class);
    }
}
