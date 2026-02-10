<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;

class PokemonSeeder extends Seeder
{
    public function run(): void
    {
        // Create types
        $types = [
            'grass', 'poison', 'fire', 'flying', 
            'water', 'bug', 'normal', 'electric',
            'ground', 'fairy', 'fighting', 'psychic',
            'rock', 'steel', 'ice', 'ghost', 'dragon'
        ];

        foreach ($types as $typeName) {
            Type::firstOrCreate(['name' => $typeName]);
        }

        // Create sample pokemons (first 10 for testing)
        $pokemons = [
            [
                'external_id' => 1,
                'name' => 'Bulbasaur',
                'height' => 7,
                'weight' => 69,
                'base_experience' => 64,
                'order' => 1,
                'species' => 'bulbasaur',
                'form' => 'default',
                'sprites' => [
                    'front_default' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/1.png',
                    'front_shiny' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/1.png',
                ],
                'is_default' => true,
                'types' => ['grass', 'poison'],
            ],
            [
                'external_id' => 4,
                'name' => 'Charmander',
                'height' => 6,
                'weight' => 85,
                'base_experience' => 62,
                'order' => 5,
                'species' => 'charmander',
                'form' => 'default',
                'sprites' => [
                    'front_default' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/4.png',
                    'front_shiny' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/4.png',
                ],
                'is_default' => true,
                'types' => ['fire'],
            ],
            [
                'external_id' => 7,
                'name' => 'Squirtle',
                'height' => 5,
                'weight' => 90,
                'base_experience' => 63,
                'order' => 10,
                'species' => 'squirtle',
                'form' => 'default',
                'sprites' => [
                    'front_default' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/7.png',
                    'front_shiny' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/7.png',
                ],
                'is_default' => true,
                'types' => ['water'],
            ],
        ];

        foreach ($pokemons as $pokemonData) {
            $types = $pokemonData['types'];
            unset($pokemonData['types']);

            $pokemon = Pokemon::firstOrCreate(
                ['external_id' => $pokemonData['external_id']],
                $pokemonData
            );

            // Attach types with proper slots
            $slot = 1;
            foreach ($types as $typeName) {
                $type = Type::where('name', $typeName)->first();
                if ($type) {
                    $pokemon->types()->syncWithoutDetaching([
                        $type->id => ['slot' => $slot]
                    ]);
                    $slot++;
                }
            }
        }
    }
}