<?php

namespace Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pokemon;
use App\Models\Type;
use PHPUnit\Framework\Attributes\Test;

class PokemonControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create 30 test Pokemon with various names for sorting tests
        $this->createTestPokemon();
    }

    private function createTestPokemon(): void
    {
        $grass = Type::create(['name' => 'grass']);
        $fire = Type::create(['name' => 'fire']);
        $water = Type::create(['name' => 'water']);

        // Create Pokemon with specific order for sorting tests
        $pokemons = [
            // Name order: Bulbasaur (1), Charmander (4), Pikachu (25), Squirtle (7)
            ['id' => 1, 'name' => 'Bulbasaur', 'type' => $grass],
            ['id' => 4, 'name' => 'Charmander', 'type' => $fire],
            ['id' => 7, 'name' => 'Squirtle', 'type' => $water],
            ['id' => 25, 'name' => 'Pikachu', 'type' => $grass], // Electric not created, using grass
        ];

        // Add 26 more Pokemon to reach 30 total
        for ($i = 30; $i <= 55; $i++) {
            $pokemons[] = ['id' => $i, 'name' => "Pokemon-{$i}", 'type' => $grass];
        }

        foreach ($pokemons as $data) {
            $pokemon = Pokemon::create([
                'external_id' => $data['id'],
                'name' => $data['name'],
                'height' => rand(1, 20),
                'weight' => rand(1, 100),
                'sprites' => ['front_default' => "https://example.com/{$data['id']}.png"],
            ]);
            
            $pokemon->types()->attach($data['type']->id, ['slot' => 1]);
        }
    }

    #[Test]
    public function it_returns_paginated_pokemon_with_default_values(): void
    {
        $response = $this->getJson('/api/v2/pokemons');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
                ],
                'metadata' => [
                    'next',
                    'previous',
                    'total',
                    'pages',
                    'page',
                ],
            ]);

        $data = $response->json();
        
        // Default limit is 20
        $this->assertCount(20, $data['data']);
        $this->assertEquals(30, $data['metadata']['total']);
        $this->assertEquals(2, $data['metadata']['pages']); // 30/20 = 1.5 → ceil = 2
        $this->assertEquals(1, $data['metadata']['page']);
        $this->assertNotNull($data['metadata']['next']);
        $this->assertNull($data['metadata']['previous']);
        
        // Check first Pokemon is ID 1 (default sort id-asc)
        $this->assertEquals(1, $data['data'][0]['id']);
    }

    #[Test]
    public function it_can_paginate_with_custom_limit(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertCount(10, $data['data']);
        $this->assertEquals(30, $data['metadata']['total']);
        $this->assertEquals(3, $data['metadata']['pages']); // 30/10 = 3
        $this->assertEquals(1, $data['metadata']['page']);
    }

    #[Test]
    public function it_can_paginate_with_custom_offset(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=10');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertCount(10, $data['data']);
        $this->assertEquals(30, $data['metadata']['total']);
        $this->assertEquals(3, $data['metadata']['pages']);
        $this->assertEquals(2, $data['metadata']['page']);
        
        // Get all Pokemon ordered by external_id
        $expectedIds = Pokemon::orderBy('external_id')
            ->skip(10)
            ->take(10)
            ->pluck('external_id')
            ->toArray();
        
        $actualIds = array_column($data['data'], 'id');
        
        $this->assertEquals($expectedIds, $actualIds);
    }

    #[Test]
    public function it_can_sort_by_id_asc(): void
    {
        $response = $this->getJson('/api/v2/pokemons?sort=id-asc&limit=5');

        $response->assertStatus(200);
        
        $data = $response->json();
        $ids = array_column($data['data'], 'id');
        
        $this->assertEquals([1, 4, 7, 25, 30], $ids); // Sorted ascending
    }

    #[Test]
    public function it_can_sort_by_id_desc(): void
    {
        $response = $this->getJson('/api/v2/pokemons?sort=id-desc&limit=5');

        $response->assertStatus(200);
        
        $data = $response->json();
        $ids = array_column($data['data'], 'id');
        
        $this->assertEquals([55, 54, 53, 52, 51], $ids); // Sorted descending
    }

    #[Test]
    public function it_can_sort_by_name_asc(): void
    {
        $response = $this->getJson('/api/v2/pokemons?sort=name-asc&limit=5');

        $response->assertStatus(200);
        
        $data = $response->json();
        $names = array_column($data['data'], 'name');
        
        // Alphabetical order
        $this->assertEquals('Bulbasaur', $names[0]);
        $this->assertEquals('Charmander', $names[1]);
    }

    #[Test]
    public function it_can_sort_by_name_desc(): void
    {
        $response = $this->getJson('/api/v2/pokemons?sort=name-desc&limit=5');

        $response->assertStatus(200);
        
        $data = $response->json();
        $names = array_column($data['data'], 'name');
        
        // Reverse alphabetical - Squirtle should be first among our named ones
        $this->assertEquals('Squirtle', $names[0]);
    }

    #[Test]
    public function it_uses_id_asc_as_default_sort(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=5');

        $response->assertStatus(200);
        
        $data = $response->json();
        $ids = array_column($data['data'], 'id');
        
        $this->assertEquals([1, 4, 7, 25, 30], $ids); // Default id-asc
    }

    #[Test]
    public function it_generates_correct_next_url(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=0');

        $data = $response->json();
        
        $this->assertNotNull($data['metadata']['next']);
        $this->assertStringContainsString('limit=10', $data['metadata']['next']);
        $this->assertStringContainsString('offset=10', $data['metadata']['next']);
        $this->assertStringContainsString('/api/v2/pokemons', $data['metadata']['next']);
    }

    #[Test]
    public function it_generates_correct_previous_url(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=20');

        $data = $response->json();
        
        $this->assertNotNull($data['metadata']['previous']);
        $this->assertStringContainsString('limit=10', $data['metadata']['previous']);
        $this->assertStringContainsString('offset=10', $data['metadata']['previous']); // Previous page offset
        $this->assertStringContainsString('/api/v2/pokemons', $data['metadata']['previous']);
    }

    #[Test]
    public function it_has_null_next_url_on_last_page(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=20');

        $data = $response->json();
        
        // Offset 20 with limit 10 gives records 21-30 (last page)
        $this->assertNull($data['metadata']['next']);
        $this->assertNotNull($data['metadata']['previous']);
    }

    #[Test]
    public function it_has_null_previous_url_on_first_page(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=0');

        $data = $response->json();
        
        $this->assertNull($data['metadata']['previous']);
        $this->assertNotNull($data['metadata']['next']);
    }

    #[Test]
    public function it_validates_limit_parameter(): void
    {
        // Test limit too high
        $response = $this->getJson('/api/v2/pokemons?limit=200');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The limit field must not be greater than 100.',
            ]);

        // Test limit too low
        $response = $this->getJson('/api/v2/pokemons?limit=0');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The limit field must be at least 1.',
            ]);

        // Test limit not integer
        $response = $this->getJson('/api/v2/pokemons?limit=abc');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The limit field must be an integer.',
            ]);
    }

    #[Test]
    public function it_validates_offset_parameter(): void
    {
        // Test offset negative
        $response = $this->getJson('/api/v2/pokemons?offset=-1');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The offset field must be at least 0.',
            ]);

        // Test offset not integer
        $response = $this->getJson('/api/v2/pokemons?offset=abc');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The offset field must be an integer.',
            ]);
    }

    #[Test]
    public function it_validates_sort_parameter(): void
    {
        // Test invalid sort value
        $response = $this->getJson('/api/v2/pokemons?sort=invalid');
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The selected sort is invalid.',
            ]);

        // Test valid sort values
        $validSorts = ['name-asc', 'name-desc', 'id-asc', 'id-desc'];
        
        foreach ($validSorts as $sort) {
            $response = $this->getJson("/api/v2/pokemons?sort={$sort}&limit=1");
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function it_preserves_query_parameters_in_next_previous_urls(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=0&sort=name-desc');

        $data = $response->json();
        
        // Next URL should preserve sort parameter
        $this->assertStringContainsString('sort=name-desc', $data['metadata']['next']);
        
        // Get second page
        $response2 = $this->getJson($data['metadata']['next']);
        $response2->assertStatus(200);
        
        $data2 = $response2->json();
        
        // Previous URL should preserve sort parameter
        $this->assertStringContainsString('sort=name-desc', $data2['metadata']['previous']);
    }

    #[Test]
    public function it_handles_empty_results_gracefully(): void
    {
        // Clear all Pokemon
        Pokemon::query()->delete();

        $response = $this->getJson('/api/v2/pokemons');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'metadata' => [
                    'next' => null,
                    'previous' => null,
                    'total' => 0,
                    'pages' => 0,
                    'page' => 1,
                ],
            ]);
    }

    #[Test]
    public function it_handles_offset_beyond_total_count(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=10&offset=100');

        $response->assertStatus(200);
        
        $data = $response->json();
        
        $this->assertCount(0, $data['data']);
        $this->assertEquals(30, $data['metadata']['total']);
        $this->assertEquals(3, $data['metadata']['pages']);
        $this->assertEquals(11, $data['metadata']['page']); // 100/10 + 1 = 11
        $this->assertNull($data['metadata']['next']);
        $this->assertNotNull($data['metadata']['previous']);
    }

    #[Test]
    public function it_returns_consistent_response_structure(): void
    {
        $response = $this->getJson('/api/v2/pokemons?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
                ],
                'metadata' => [
                    'next',
                    'previous',
                    'total',
                    'pages',
                    'page',
                ],
            ]);
    }
}
