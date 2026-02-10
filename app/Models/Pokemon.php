<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pokemon extends Model
{
    protected $table = 'pokemons';

    protected $fillable = [
        'external_id',
        'name',
        'height',
        'weight',
        'base_experience',
        'order',
        'species',
        'form',
        'sprites',
        'is_default',
    ];

    protected $casts = [
        'sprites' => 'array',
        'is_default' => 'boolean',
    ];

    public $timestamps = true;

    /**
     * Get the types for this pokemon
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'pokemon_types')
            ->withPivot('slot')
            ->orderBy('slot');
    }

    /**
     * Get the abilities for this pokemon
     */
    public function abilities(): BelongsToMany
    {
        return $this->belongsToMany(Ability::class, 'pokemon_abilities')
            ->withPivot('is_hidden', 'slot')
            ->orderBy('slot');
    }

    /**
     * Get the moves for this pokemon
     */
    public function moves(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'pokemon_moves');
    }

    /**
     * Get the stats for this pokemon
     */
    public function stats(): BelongsToMany
    {
        return $this->belongsToMany(Stat::class, 'pokemon_stats')
            ->withPivot('base_stat', 'effort');
    }

    /**
     * Get the teams this pokemon belongs to
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_pokemons')
            ->withTimestamps();
    }
}
