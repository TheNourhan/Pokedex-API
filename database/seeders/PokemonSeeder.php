<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;
use App\Models\Move;
use App\Models\Stat;

class PokemonSeeder extends Seeder
{
    public function run(): void
    {
        // Create types
        Type::firstOrCreate(['name' => 'grass']);
        Type::firstOrCreate(['name' => 'poison']);
        Type::firstOrCreate(['name' => 'fire']);
        Type::firstOrCreate(['name' => 'water']);
        Type::firstOrCreate(['name' => 'electric']);

        // Create abilities
        Ability::firstOrCreate(['name' => 'overgrow']);
        Ability::firstOrCreate(['name' => 'chlorophyll']);
        Ability::firstOrCreate(['name' => 'blaze']);
        Ability::firstOrCreate(['name' => 'solar-power']);
        Ability::firstOrCreate(['name' => 'torrent']);
        Ability::firstOrCreate(['name' => 'rain-dish']);

        // Create moves
        Move::firstOrCreate(['name' => 'tackle']);
        Move::firstOrCreate(['name' => 'growl']);
        Move::firstOrCreate(['name' => 'vine-whip']);
        Move::firstOrCreate(['name' => 'scratch']);
        Move::firstOrCreate(['name' => 'ember']);
        Move::firstOrCreate(['name' => 'water-gun']);

        // Create stats
        $stats = [
            'hp', 'attack', 'defense', 'special-attack', 
            'special-defense', 'speed'
        ];
        foreach ($stats as $statName) {
            Stat::firstOrCreate(['name' => $statName]);
        }

        // Create Bulbasaur with full data
        $bulbasaur = Pokemon::firstOrCreate(
            ['external_id' => 1],
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
                    'back_default' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/1.png',
                    'back_shiny' => 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/back/shiny/1.png',
                ],
                'is_default' => true,
            ]
        );

        // Attach types to Bulbasaur
        $bulbasaur->types()->sync([
            Type::where('name', 'grass')->first()->id => ['slot' => 1],
            Type::where('name', 'poison')->first()->id => ['slot' => 2],
        ]);

        // Attach abilities to Bulbasaur
        $bulbasaur->abilities()->sync([
            Ability::where('name', 'overgrow')->first()->id => ['slot' => 1, 'is_hidden' => false],
            Ability::where('name', 'chlorophyll')->first()->id => ['slot' => 3, 'is_hidden' => true],
        ]);

        // Attach stats to Bulbasaur
        $bulbasaur->stats()->sync([
            Stat::where('name', 'hp')->first()->id => ['base_stat' => 45, 'effort' => 0],
            Stat::where('name', 'attack')->first()->id => ['base_stat' => 49, 'effort' => 0],
            Stat::where('name', 'defense')->first()->id => ['base_stat' => 49, 'effort' => 0],
            Stat::where('name', 'special-attack')->first()->id => ['base_stat' => 65, 'effort' => 1],
            Stat::where('name', 'special-defense')->first()->id => ['base_stat' => 65, 'effort' => 0],
            Stat::where('name', 'speed')->first()->id => ['base_stat' => 45, 'effort' => 0],
        ]);

        // Attach moves to Bulbasaur
        $bulbasaur->moves()->sync([
            Move::where('name', 'tackle')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 1, 'move_learn_method' => 'level-up']])
            ],
            Move::where('name', 'growl')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 1, 'move_learn_method' => 'level-up']])
            ],
            Move::where('name', 'vine-whip')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 7, 'move_learn_method' => 'level-up']])
            ],
        ]);

        // Create Charmander
        $charmander = Pokemon::firstOrCreate(
            ['external_id' => 4],
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
                ],
                'is_default' => true,
            ]
        );

        $charmander->types()->sync([
            Type::where('name', 'fire')->first()->id => ['slot' => 1],
        ]);

        $charmander->stats()->sync([
            Stat::where('name', 'hp')->first()->id => ['base_stat' => 39, 'effort' => 0],
            Stat::where('name', 'attack')->first()->id => ['base_stat' => 52, 'effort' => 0],
            Stat::where('name', 'defense')->first()->id => ['base_stat' => 43, 'effort' => 0],
            Stat::where('name', 'special-attack')->first()->id => ['base_stat' => 60, 'effort' => 0],
            Stat::where('name', 'special-defense')->first()->id => ['base_stat' => 50, 'effort' => 0],
            Stat::where('name', 'speed')->first()->id => ['base_stat' => 65, 'effort' => 1],
        ]);

        $charmander->abilities()->sync([
            Ability::where('name', 'blaze')->first()->id => ['slot' => 1, 'is_hidden' => false],
            Ability::where('name', 'solar-power')->first()->id => ['slot' => 3, 'is_hidden' => true],
        ]);

        $charmander->moves()->sync([
            Move::where('name', 'scratch')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 1, 'move_learn_method' => 'level-up']])
            ],
            Move::where('name', 'ember')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 7, 'move_learn_method' => 'level-up']])
            ],
        ]);

        // Create Squirtle
        $squirtle = Pokemon::firstOrCreate(
            ['external_id' => 7],
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
                ],
                'is_default' => true,
            ]
        );

        $squirtle->types()->sync([
            Type::where('name', 'water')->first()->id => ['slot' => 1],
        ]);

        $squirtle->stats()->sync([
            Stat::where('name', 'hp')->first()->id => ['base_stat' => 44, 'effort' => 0],
            Stat::where('name', 'attack')->first()->id => ['base_stat' => 48, 'effort' => 0],
            Stat::where('name', 'defense')->first()->id => ['base_stat' => 65, 'effort' => 1],
            Stat::where('name', 'special-attack')->first()->id => ['base_stat' => 50, 'effort' => 0],
            Stat::where('name', 'special-defense')->first()->id => ['base_stat' => 64, 'effort' => 0],
            Stat::where('name', 'speed')->first()->id => ['base_stat' => 43, 'effort' => 0],
        ]);

        // Attach abilities to Squirtle
        $squirtle->abilities()->sync([
            Ability::where('name', 'torrent')->first()->id => ['slot' => 1, 'is_hidden' => false],
            Ability::where('name', 'rain-dish')->first()->id => ['slot' => 3, 'is_hidden' => true],
        ]);

        // Attach moves to Squirtle
        $squirtle->moves()->sync([
            Move::where('name', 'tackle')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 1, 'move_learn_method' => 'level-up']])
            ],
            Move::where('name', 'water-gun')->first()->id => [
                'version_group_details' => json_encode([['level_learned_at' => 7, 'move_learn_method' => 'level-up']])
            ],
        ]);
    }
}