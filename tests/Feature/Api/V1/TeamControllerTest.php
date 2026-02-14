<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Pokemon;
use App\Models\Type;
use PHPUnit\Framework\Attributes\Test;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $token = 'pokemon-master-2026';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('team.auth_token', 'pokemon-master-2026');
        putenv('TEAM_AUTH_TOKEN=pokemon-master-2026');

        // Create a test Pokemon
        $grass = Type::create(['name' => 'grass']);
        $bulbasaur = Pokemon::create([
            'external_id' => 1,
            'name' => 'Bulbasaur',
            'sprites' => ['front_default' => 'test.png'],
        ]);
        $bulbasaur->types()->attach($grass->id, ['slot' => 1]);
    }

    #[Test]
    public function authentication_required_for_team_routes()
    {
        $response = $this->getJson('/api/v1/teams');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'error_message' => 'Invalid or missing authorization token',
            ]);
    }

    #[Test]
    public function can_get_all_teams()
    {
        Team::create(['name' => 'Team 1']);
        Team::create(['name' => 'Team 2']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'pokemons',
                    // 'created_at',
                    // 'updated_at',
                ],
            ]);
    }

    #[Test]
    public function can_create_team()
    {
        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams', [
                'name' => 'My New Team',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'My New Team',
                'pokemons' => [],
            ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'My New Team',
        ]);
    }

    #[Test]
    public function cannot_create_team_with_duplicate_name()
    {
        Team::create(['name' => 'My Team']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams', [
                'name' => 'My Team',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'A team with this name already exists',
            ]);
    }

    #[Test]
    public function can_get_single_team()
    {
        $team = Team::create(['name' => 'My Team']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->getJson('/api/v1/teams/' . $team->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $team->id,
                'name' => 'My Team',
                'pokemons' => [],
            ]);
    }

    #[Test]
    public function returns_404_for_non_existent_team()
    {
        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->getJson('/api/v1/teams/999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Not Found',
                'error_message' => 'Resource not found',
            ]);
    }

    #[Test]
    public function can_add_pokemons_to_team()
    {
        $team = Team::create(['name' => 'My Team']);
        
        $pokemon = Pokemon::where('external_id', 1)->first();

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams/' . $team->id, [
                'pokemons' => [1], // external_id
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $team->id,
                'name' => 'My Team',
                'pokemons' => [1], // API returns external_id
            ]);

        // Use the actual internal ID from the database
        $this->assertDatabaseHas('team_pokemons', [
            'team_id' => $team->id,
            'pokemon_id' => $pokemon->id, // internal ID
        ]);
    }

    #[Test]
    public function cannot_add_more_than_6_pokemons_to_team()
    {
        $team = Team::create(['name' => 'My Team']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams/' . $team->id, [
                'pokemons' => [1, 2, 3, 4, 5, 6, 7],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'Team cannot have more than 6 pokemons',
            ]);
    }

    #[Test]
    public function cannot_add_duplicate_pokemons_to_team()
    {
        $team = Team::create(['name' => 'My Team']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams/' . $team->id, [
                'pokemons' => [1, 1, 1],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'Duplicate pokemon IDs are not allowed.',
            ]);
    }

    #[Test]
    public function cannot_add_non_existent_pokemon_to_team()
    {
        $team = Team::create(['name' => 'My Team']);

        $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])
            ->postJson('/api/v1/teams/' . $team->id, [
                'pokemons' => [999],
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation Error',
                'error_message' => 'Pokemon with ID 999 does not exist',
            ]);
    }

    #[Test]
    public function token_works_without_bearer_prefix()
    {
        $response = $this->withHeaders([
                'Authorization' => $this->token,
            ])
            ->getJson('/api/v1/teams');

        $response->assertStatus(200);
    }
}
