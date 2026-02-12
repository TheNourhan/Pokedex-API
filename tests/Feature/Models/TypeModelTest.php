<?php

namespace Tests\Feature\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Type;
use App\Models\Pokemon;

class TypeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_type(): void
    {
        $type = Type::create(['name' => 'fire']);

        $this->assertDatabaseHas('types', ['name' => 'fire']);
        $this->assertEquals('fire', $type->name);
    }

    public function test_type_can_have_pokemons(): void
    {
        $type = Type::create(['name' => 'grass']);
        
        $pokemon = Pokemon::create([
            'external_id' => 1,
            'name' => 'bulbasaur',
            'sprites' => ['front_default' => 'test.png'],
        ]);

        $pokemon->types()->attach($type->id, ['slot' => 1]);

        $this->assertCount(1, $type->pokemons);
        $this->assertEquals('bulbasaur', $type->pokemons->first()->name);
        $this->assertEquals(1, $type->pokemons->first()->pivot->slot);
    }
}
