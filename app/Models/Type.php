<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Type extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    /**
     * Get the pokemons with this type
     */
    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_types')
            ->withPivot('slot');
    }
}
