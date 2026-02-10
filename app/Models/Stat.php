<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Stat extends Model
{
    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    /**
     * Get the pokemons with this stat
     */
    public function pokemons(): BelongsToMany
    {
        return $this->belongsToMany(Pokemon::class, 'pokemon_stats')
            ->withPivot('base_stat', 'effort');
    }
}
