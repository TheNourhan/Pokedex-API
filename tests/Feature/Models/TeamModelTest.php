<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Team;
use App\Models\Pokemon;

class TeamModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_team(): void
    {
        $team = Team::create(['name' => 'My Team']);

        $this->assertDatabaseHas('teams', ['name' => 'My Team']);
        $this->assertEquals('My Team', $team->name);
    }

    public function test_team_has_max_pokemons_constant(): void
    {
        $this->assertEquals(6, Team::MAX_POKEMONS);
    }

    public function test_team_can_have_pokemons(): void
    {
        $team = Team::create(['name' => 'Starter Team']);
        
        $pokemon1 = Pokemon::create([
            'external_id' => 1,
            'name' => 'bulbasaur',
            'sprites' => ['front_default' => 'test.png'],
        ]);
        
        $pokemon2 = Pokemon::create([
            'external_id' => 4,
            'name' => 'charmander',
            'sprites' => ['front_default' => 'test.png'],
        ]);

        $team->pokemons()->attach([$pokemon1->id, $pokemon2->id]);

        $this->assertCount(2, $team->pokemons);
        $this->assertEquals('bulbasaur', $team->pokemons[0]->name);
        $this->assertEquals('charmander', $team->pokemons[1]->name);
    }

    public function test_team_has_timestamps(): void
    {
        $team = Team::create(['name' => 'My Team']);
        
        $this->assertNotNull($team->created_at);
        $this->assertNotNull($team->updated_at);
    }
}
