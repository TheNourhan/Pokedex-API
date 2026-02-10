<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ability extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    /**
     * Get the pokemons with this ability
     */
    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_abilities')
            ->withPivot('is_hidden', 'slot');
    }
}
