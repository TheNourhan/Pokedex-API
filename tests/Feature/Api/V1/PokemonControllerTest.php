<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pokemon;
use App\Models\Type;

class PokemonControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $grass = Type::create(['name' => 'grass']);
        $poison = Type::create(['name' => 'poison']);
        
        $pokemon = Pokemon::create([
            'external_id' => 1,
            'name' => 'Bulbasaur',
            'height' => 7,
            'weight' => 69,
            'sprites' => [
                'front_default' => 'https://example.com/bulbasaur.png',
            ],
        ]);
        
        $pokemon->types()->attach([
            $grass->id => ['slot' => 1],
            $poison->id => ['slot' => 2],
        ]);
    }

    public function test_can_get_all_pokemons(): void
    {
        $response = $this->getJson('/api/v1/pokemons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'sprites' => ['front_default'],
                    'types' => [
                        '*' => [
                            'type' => ['name'],
                            'slot',
                        ],
                    ],
                ],
            ]);
        
        $this->assertCount(1, $response->json());
    }

    public function test_can_get_single_pokemon(): void
    {
        $response = $this->getJson('/api/v1/pokemons/1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'sprites' => [
                    'front_default',
                    'front_female',
                    'front_shiny',
                    'front_shiny_female',
                    'back_default',
                    'back_female',
                    'back_shiny',
                    'back_shiny_female',
                ],
                'types',
                'height',
                'weight',
                'moves',
                'order',
                'species',
                'stats',
                'abilities',
                'form',
            ])
            ->assertJson([
                'id' => 1,
                'name' => 'Bulbasaur',
            ]);
    }

    public function test_returns_404_for_non_existent_pokemon(): void
    {
        $response = $this->getJson('/api/v1/pokemons/999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Not Found',
                'error_message' => 'Pokemon not found',
            ]);
    }

    public function test_can_sort_pokemons_by_name_asc(): void
    {
        // Create second pokemon
        $fire = Type::create(['name' => 'fire']);
        $charmander = Pokemon::create([
            'external_id' => 4,
            'name' => 'Charmander',
            'sprites' => ['front_default' => 'https://example.com/charmander.png'],
        ]);
        $charmander->types()->attach($fire->id, ['slot' => 1]);

        $response = $this->getJson('/api/v1/pokemons?sort=name-asc');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals('Bulbasaur', $data[0]['name']);
        $this->assertEquals('Charmander', $data[1]['name']);
    }

    public function test_can_sort_pokemons_by_id_desc(): void
    {
        // Create second pokemon
        $fire = Type::create(['name' => 'fire']);
        $charmander = Pokemon::create([
            'external_id' => 4,
            'name' => 'Charmander',
            'sprites' => ['front_default' => 'https://example.com/charmander.png'],
        ]);
        $charmander->types()->attach($fire->id, ['slot' => 1]);

        $response = $this->getJson('/api/v1/pokemons?sort=id-desc');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals(4, $data[0]['id']);
        $this->assertEquals(1, $data[1]['id']);
    }
}
