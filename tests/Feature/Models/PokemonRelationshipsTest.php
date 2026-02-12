<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;
// use App\Models\Move;
use App\Models\Stat;
use App\Models\Team;

class PokemonRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    private Pokemon $pokemon;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pokemon = Pokemon::create([
            'external_id' => 1,
            'name' => 'bulbasaur',
            'height' => 7,
            'weight' => 69,
            'sprites' => ['front_default' => 'test.png'],
        ]);
    }

    public function test_pokemon_belongs_to_many_types(): void
    {
        $grass = Type::create(['name' => 'grass']);
        $poison = Type::create(['name' => 'poison']);

        $this->pokemon->types()->attach([
            $grass->id => ['slot' => 1],
            $poison->id => ['slot' => 2],
        ]);

        $this->pokemon->load('types');

        $this->assertCount(2, $this->pokemon->types);
        $this->assertEquals('grass', $this->pokemon->types[0]->name);
        $this->assertEquals(1, $this->pokemon->types[0]->pivot->slot);
        $this->assertEquals('poison', $this->pokemon->types[1]->name);
        $this->assertEquals(2, $this->pokemon->types[1]->pivot->slot);
    }

    public function test_pokemon_belongs_to_many_abilities(): void
    {
        $overgrow = Ability::create(['name' => 'overgrow']);
        $chlorophyll = Ability::create(['name' => 'chlorophyll']);

        $this->pokemon->abilities()->attach([
            $overgrow->id => ['slot' => 1, 'is_hidden' => 0],
            $chlorophyll->id => ['slot' => 3, 'is_hidden' => 1],
        ]);

        $this->pokemon->load('abilities');

        $this->assertCount(2, $this->pokemon->abilities);
        $this->assertEquals('overgrow', $this->pokemon->abilities[0]->name);
        $this->assertEquals(0, $this->pokemon->abilities[0]->pivot->is_hidden);
        $this->assertEquals(1, $this->pokemon->abilities[0]->pivot->slot);
        
        $this->assertEquals('chlorophyll', $this->pokemon->abilities[1]->name);
        $this->assertEquals(1, $this->pokemon->abilities[1]->pivot->is_hidden);
        $this->assertEquals(3, $this->pokemon->abilities[1]->pivot->slot);
    }

    public function test_pokemon_belongs_to_many_stats(): void
    {
        $hp = Stat::create(['name' => 'hp']);
        $attack = Stat::create(['name' => 'attack']);

        $this->pokemon->stats()->attach([
            $hp->id => ['base_stat' => 45, 'effort' => 0],
            $attack->id => ['base_stat' => 49, 'effort' => 0],
        ]);

        $this->pokemon->load('stats');

        $this->assertCount(2, $this->pokemon->stats);
        $this->assertEquals('hp', $this->pokemon->stats[0]->name);
        $this->assertEquals(45, $this->pokemon->stats[0]->pivot->base_stat);
    }

    // TODO: test_pokemon_belongs_to_many_moves
    // public function test_pokemon_belongs_to_many_moves(): void
    // {

    // }

    public function test_pokemon_belongs_to_many_teams(): void
    {
        $team = Team::create(['name' => 'My Team']);

        $this->pokemon->teams()->attach($team->id);

        $this->pokemon->load('teams');

        $this->assertCount(1, $this->pokemon->teams);
        $this->assertEquals('My Team', $this->pokemon->teams->first()->name);
    }
}
