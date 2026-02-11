<?php

namespace App\Console\Commands;

use App\Models\Pokemon;
use App\Models\Type;
use App\Models\Ability;
use App\Models\Move;
use App\Models\Stat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportPokemonFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pokemon:import-from-api 
                            {identifier : Pokémon ID or name (e.g., 25 or pikachu)}
                            {--force : Update existing Pokémon even if already imported}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a Pokémon from PokeAPI by ID or name';

    /**
     * Base URL for PokeAPI v2
     */
    protected const POKEAPI_URL = 'https://pokeapi.co/api/v2/pokemon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = strtolower($this->argument('identifier'));
        $force = $this->option('force');

        $this->info('====================================');
        $this->info('   POKEMON API IMPORTER');
        $this->info('====================================');
        $this->line("🔍 Searching for: {$identifier}");

        // Check if Pokémon already exists
        $existing = Pokemon::where('external_id', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if ($existing && !$force) {
            $this->warn("⚠️  Pokémon '{$existing->name}' (ID: {$existing->external_id}) already exists in database!");
            $this->line('Use --force to update existing Pokémon.');
            return Command::FAILURE;
        }

        // Fetch from PokeAPI
        $this->line("🌐 Fetching from PokeAPI...");
        
        try {
            $response = Http::get(self::POKEAPI_URL . '/' . $identifier);
            
            if ($response->failed()) {
                if ($response->status() === 404) {
                    $this->error("❌ Pokémon not found: {$identifier}");
                } else {
                    $this->error("❌ API request failed with status: " . $response->status());
                }
                return Command::FAILURE;
            }

            $data = $response->json();
            $this->info("✅ Found: {$data['name']} (ID: {$data['id']})");
            $this->line('');

            // Confirm import
            if (!$this->confirm('Import this Pokémon?', true)) {
                $this->info('Import cancelled.');
                return Command::SUCCESS;
            }

            $this->line('');
            $this->info('🔄 Importing...');

            // Process the import
            DB::beginTransaction();
            
            try {
                $pokemon = $this->createOrUpdatePokemon($data, $force);
                $this->processTypes($pokemon, $data['types'] ?? []);
                $this->processAbilities($pokemon, $data['abilities'] ?? []);
                $this->processStats($pokemon, $data['stats'] ?? []);
                $this->processMoves($pokemon, $data['moves'] ?? []);
                
                DB::commit();
                
                $this->line('');
                $this->info('✅ Import completed successfully!');
                $this->line('');
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['ID', $pokemon->external_id],
                        ['Name', $pokemon->name],
                        ['Height', $pokemon->height . ' dm'],
                        ['Weight', $pokemon->weight . ' hg'],
                        ['Types', $pokemon->types->pluck('name')->implode(', ')],
                        ['Abilities', $pokemon->abilities->count()],
                        ['Moves', $pokemon->moves->count()],
                    ]
                );
                
                return Command::SUCCESS;
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create or update a Pokemon record from API data
     */
    protected function createOrUpdatePokemon(array $data, bool $force): Pokemon
    {
        $sprites = $data['sprites'] ?? [];
        
        // Flatten sprites - we only want the default sprites, not other/generations
        $flattenedSprites = [
            'front_default' => $sprites['front_default'] ?? null,
            'front_female' => $sprites['front_female'] ?? null,
            'front_shiny' => $sprites['front_shiny'] ?? null,
            'front_shiny_female' => $sprites['front_shiny_female'] ?? null,
            'back_default' => $sprites['back_default'] ?? null,
            'back_female' => $sprites['back_female'] ?? null,
            'back_shiny' => $sprites['back_shiny'] ?? null,
            'back_shiny_female' => $sprites['back_shiny_female'] ?? null,
        ];

        $species = $data['species']['name'] ?? null;
        $form = $data['forms'][0]['name'] ?? null;

        return Pokemon::updateOrCreate(
            ['external_id' => $data['id']],
            [
                'name' => $data['name'],
                'height' => $data['height'] ?? null,
                'weight' => $data['weight'] ?? null,
                'base_experience' => $data['base_experience'] ?? null,
                'order' => $data['order'] ?? null,
                'species' => $species,
                'form' => $form,
                'sprites' => $flattenedSprites,
                'is_default' => $data['is_default'] ?? true,
            ]
        );
    }

    /**
     * Process and attach types to Pokemon
     */
    protected function processTypes(Pokemon $pokemon, array $types): void
    {
        $syncData = [];
        
        foreach ($types as $typeData) {
            $typeName = $typeData['type']['name'] ?? null;
            $slot = $typeData['slot'] ?? null;

            if ($typeName && $slot) {
                $type = Type::firstOrCreate(['name' => $typeName]);
                $syncData[$type->id] = ['slot' => $slot];
            }
        }

        if (!empty($syncData)) {
            $pokemon->types()->sync($syncData);
        }
    }

    /**
     * Process and attach abilities to Pokemon
     */
    protected function processAbilities(Pokemon $pokemon, array $abilities): void
    {
        $syncData = [];
        
        foreach ($abilities as $abilityData) {
            $abilityName = $abilityData['ability']['name'] ?? null;
            $slot = $abilityData['slot'] ?? null;
            $isHidden = $abilityData['is_hidden'] ?? false;

            if ($abilityName && $slot) {
                $ability = Ability::firstOrCreate(['name' => $abilityName]);
                $syncData[$ability->id] = [
                    'slot' => $slot,
                    'is_hidden' => $isHidden,
                ];
            }
        }

        if (!empty($syncData)) {
            $pokemon->abilities()->sync($syncData);
        }
    }

    /**
     * Process and attach stats to Pokemon
     */
    protected function processStats(Pokemon $pokemon, array $stats): void
    {
        $syncData = [];
        
        foreach ($stats as $statData) {
            $statName = $statData['stat']['name'] ?? null;
            $baseStat = $statData['base_stat'] ?? null;
            $effort = $statData['effort'] ?? 0;

            if ($statName && $baseStat !== null) {
                $stat = Stat::firstOrCreate(['name' => $statName]);
                $syncData[$stat->id] = [
                    'base_stat' => $baseStat,
                    'effort' => $effort,
                ];
            }
        }

        if (!empty($syncData)) {
            $pokemon->stats()->sync($syncData);
        }
    }

    /**
     * Process and attach moves to Pokemon
     */
    protected function processMoves(Pokemon $pokemon, array $moves): void
    {
        $syncData = [];
        
        // PokeAPI returns a lot of moves, we'll limit to first 20 to avoid performance issues
        $movesToProcess = array_slice($moves, 0, 20);
        
        foreach ($movesToProcess as $moveData) {
            $moveName = $moveData['move']['name'] ?? null;
            
            if ($moveName) {
                $move = Move::firstOrCreate(['name' => $moveName]);
                
                // Transform version group details
                $versionDetails = [];
                foreach ($moveData['version_group_details'] ?? [] as $detail) {
                    $versionDetails[] = [
                        'level_learned_at' => $detail['level_learned_at'] ?? 0,
                        'move_learn_method' => $detail['move_learn_method']['name'] ?? null,
                        'version_group' => $detail['version_group']['name'] ?? null,
                    ];
                }

                $syncData[$move->id] = [
                    'version_group_details' => json_encode($versionDetails),
                ];
            }
        }

        if (!empty($syncData)) {
            $pokemon->moves()->syncWithoutDetaching($syncData); // Don't remove existing moves
        }
    }
}
