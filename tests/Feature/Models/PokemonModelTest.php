<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pokemon;

class PokemonModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pokemon(): void
    {
        $pokemon = Pokemon::create([
            'external_id' => 25,
            'name' => 'pikachu',
            'height' => 4,
            'weight' => 60,
            'sprites' => ['front_default' => 'https://example.com/pikachu.png'],
        ]);

        $this->assertDatabaseHas('pokemons', [
            'external_id' => 25,
            'name' => 'pikachu',
        ]);
        
        $this->assertEquals(25, $pokemon->external_id);
        $this->assertEquals('pikachu', $pokemon->name);
    }

    public function test_pokemon_has_fillable_attributes(): void
    {
        $pokemon = new Pokemon();
        
        $fillable = [
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
        
        $this->assertEquals($fillable, $pokemon->getFillable());
    }

    public function test_pokemon_casts_sprites_to_array(): void
    {
        $pokemon = Pokemon::create([
            'external_id' => 1,
            'name' => 'bulbasaur',
            'sprites' => [
                'front_default' => 'test.png',
                'back_default' => 'back.png',
            ],
        ]);

        $this->assertIsArray($pokemon->sprites);
        $this->assertEquals('test.png', $pokemon->sprites['front_default']);
        $this->assertEquals('back.png', $pokemon->sprites['back_default']);
    }
}
