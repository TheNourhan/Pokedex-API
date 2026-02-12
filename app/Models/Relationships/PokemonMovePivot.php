<?php

namespace App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PokemonMovePivot extends Pivot
{
    protected $table = 'pokemon_moves';

    protected $casts = [
        'version_group_details' => 'array',
    ];

    public $timestamps = false;
}