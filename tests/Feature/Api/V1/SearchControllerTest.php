<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pokemon;
use App\Models\Type;
use PHPUnit\Framework\Attributes\Test;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test Pokemon
        $this->createTestPokemon();
    }

    private function createTestPokemon(): void
    {
        // Create types
        $grass = Type::create(['name' => 'grass']);
        $poison = Type::create(['name' => 'poison']);
        $fire = Type::create(['name' => 'fire']);
        $water = Type::create(['name' => 'water']);
        $electric = Type::create(['name' => 'electric']);

        // Bulbasaur (ID: 1)
        $bulbasaur = Pokemon::create([
            'external_id' => 1,
            'name' => 'Bulbasaur',
            'height' => 7,
            'weight' => 69,
            'sprites' => ['front_default' => 'https://example.com/1.png'],
        ]);
        $bulbasaur->types()->attach([
            $grass->id => ['slot' => 1],
            $poison->id => ['slot' => 2],
        ]);

        // Ivysaur (ID: 2)
        $ivysaur = Pokemon::create([
            'external_id' => 2,
            'name' => 'Ivysaur',
            'height' => 10,
            'weight' => 130,
            'sprites' => ['front_default' => 'https://example.com/2.png'],
        ]);
        $ivysaur->types()->attach([
            $grass->id => ['slot' => 1],
            $poison->id => ['slot' => 2],
        ]);

        // Charmander (ID: 4)
        $charmander = Pokemon::create([
            'external_id' => 4,
            'name' => 'Charmander',
            'height' => 6,
            'weight' => 85,
            'sprites' => ['front_default' => 'https://example.com/4.png'],
        ]);
        $charmander->types()->attach($fire->id, ['slot' => 1]);

        // Charmeleon (ID: 5)
        $charmeleon = Pokemon::create([
            'external_id' => 5,
            'name' => 'Charmeleon',
            'height' => 11,
            'weight' => 190,
            'sprites' => ['front_default' => 'https://example.com/5.png'],
        ]);
        $charmeleon->types()->attach($fire->id, ['slot' => 1]);

        // Squirtle (ID: 7)
        $squirtle = Pokemon::create([
            'external_id' => 7,
            'name' => 'Squirtle',
            'height' => 5,
            'weight' => 90,
            'sprites' => ['front_default' => 'https://example.com/7.png'],
        ]);
        $squirtle->types()->attach($water->id, ['slot' => 1]);

        // Pikachu (ID: 25)
        $pikachu = Pokemon::create([
            'external_id' => 25,
            'name' => 'Pikachu',
            'height' => 4,
            'weight' => 60,
            'sprites' => ['front_default' => 'https://example.com/25.png'],
        ]);
        $pikachu->types()->attach($electric->id, ['slot' => 1]);
    }

    #[Test]
    public function it_can_search_pokemon_by_name_partial_match(): void
    {
        $response = $this->getJson('/api/v1/search?query=char');

        $response->assertStatus(200)
            ->assertJsonCount(2) // Charmander and Charmeleon
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

        $results = $response->json();
        $names = array_column($results, 'name');
        
        $this->assertContains('Charmander', $names);
        $this->assertContains('Charmeleon', $names);
        $this->assertNotContains('Bulbasaur', $names);
    }

    #[Test]
    public function it_can_search_pokemon_by_name_case_insensitive(): void
    {
        $response = $this->getJson('/api/v1/search?query=CHAR');

        $response->assertStatus(200)
            ->assertJsonCount(2); // Charmander and Charmeleon

        $results = $response->json();
        $names = array_column($results, 'name');
        
        $this->assertContains('Charmander', $names);
        $this->assertContains('Charmeleon', $names);
    }

    #[Test]
    public function it_can_search_pokemon_by_exact_name(): void
    {
        $response = $this->getJson('/api/v1/search?query=Pikachu');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'id' => 25,
                    'name' => 'Pikachu',
                    'types' => [
                        [
                            'type' => ['name' => 'electric'],
                            'slot' => 1,
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_can_search_pokemon_by_type(): void
    {
        $response = $this->getJson('/api/v1/search?query=fire');

        $response->assertStatus(200)
            ->assertJsonCount(2); // Charmander and Charmeleon

        $results = $response->json();
        
        foreach ($results as $pokemon) {
            $types = array_column($pokemon['types'], 'type');
            $typeNames = array_column($types, 'name');
            $this->assertContains('fire', $typeNames);
        }
    }

    #[Test]
    public function it_can_search_pokemon_by_partial_type_match(): void
    {
        $response = $this->getJson('/api/v1/search?query=elec');

        $response->assertStatus(200)
            ->assertJsonCount(1) // Pikachu
            ->assertJson([
                [
                    'id' => 25,
                    'name' => 'Pikachu',
                ],
            ]);
    }

    #[Test]
    public function it_can_limit_search_results(): void
    {
        // Create 5 Pokemon with similar names
        for ($i = 1; $i <= 5; $i++) {
            Pokemon::create([
                'external_id' => 100 + $i,
                'name' => 'TestPokemon' . $i,
                'sprites' => ['front_default' => 'test.png'],
            ]);
        }

        $response = $this->getJson('/api/v1/search?query=TestPokemon&limit=2');

        $response->assertStatus(200)
            ->assertJsonCount(2);
        
        $response = $this->getJson('/api/v1/search?query=TestPokemon');
        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    #[Test]
    public function it_returns_empty_array_when_no_results(): void
    {
        $response = $this->getJson('/api/v1/search?query=xyzabc');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    #[Test]
    public function it_validates_required_query_parameter(): void
    {
        $response = $this->getJson('/api/v1/search');

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The query field is required.',
            ]);
    }

    #[Test]
    public function it_validates_query_minimum_length(): void
    {
        $response = $this->getJson('/api/v1/search?query=a');

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The query field must be at least 2 characters.',
            ]);
    }

    #[Test]
    public function it_validates_query_maximum_length(): void
    {
        $longQuery = str_repeat('a', 51);
        $response = $this->getJson('/api/v1/search?query=' . $longQuery);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The query field must not be greater than 50 characters.',
            ]);
    }

    #[Test]
    public function it_validates_limit_parameter(): void
    {
        $response = $this->getJson('/api/v1/search?query=char&limit=200');

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'The limit field must not be greater than 100.',
            ]);
    }

    #[Test]
    public function it_returns_correct_pokemon_structure(): void
    {
        $response = $this->getJson('/api/v1/search?query=Bulbasaur');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'sprites' => [
                        'front_default',
                    ],
                    'types' => [
                        '*' => [
                            'type' => [
                                'name',
                            ],
                            'slot',
                        ],
                    ],
                ],
            ]);
        
        $data = $response->json()[0];
        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['sprites']['front_default']);
        $this->assertIsArray($data['types']);
    }

    #[Test]
    public function it_searches_both_name_and_type_simultaneously(): void
    {
        // Create a Pokemon with "fire" in name AND fire type
        $fire = Type::where('name', 'fire')->first();
        $flareon = Pokemon::create([
            'external_id' => 136,
            'name' => 'Flareon',
            'height' => 9,
            'weight' => 250,
            'sprites' => ['front_default' => 'https://example.com/136.png'],
        ]);
        $flareon->types()->attach($fire->id, ['slot' => 1]);

        // Search for "fire" - should return both Charmander/Charmeleon (fire type) AND Flareon (name contains "fire")
        $response = $this->getJson('/api/v1/search?query=fire');

        $response->assertStatus(200);
        $results = $response->json();
        $names = array_column($results, 'name');
        
        $this->assertContains('Charmander', $names);
        $this->assertContains('Charmeleon', $names);
        $this->assertContains('Flareon', $names);
    }

    #[Test]
    public function it_trims_whitespace_from_query(): void
    {
        $response = $this->call('GET', '/api/v1/search', [
            'query' => '  pikachu  '
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'id' => 25,
                    'name' => 'Pikachu',
                ],
            ]);
    }

    #[Test]
    public function it_uses_default_limit_of_20(): void
    {
        // Create 25 Pokemon
        for ($i = 100; $i < 125; $i++) {
            Pokemon::create([
                'external_id' => $i,
                'name' => 'Test-' . $i,
                'sprites' => ['front_default' => 'test.png'],
            ]);
        }

        $response = $this->getJson('/api/v1/search?query=Test');

        $response->assertStatus(200);
        $this->assertCount(20, $response->json()); // Default limit is 20
    }
}
