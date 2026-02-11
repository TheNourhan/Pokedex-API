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
use Illuminate\Support\Facades\Storage;

class ImportPokemonJsonDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pokemon:import-dump 
                            {file? : Path to pokemons.json file (default: storage/app/pokemons.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Pokemon from the original 151 JSON dump';

    /**
     * Statistics for the import
     */
    protected array $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================');
        $this->info('   POKEMON JSON DUMP IMPORTER');
        $this->info('====================================');

        // Get the JSON file path
        $filePath = $this->argument('file') ?? storage_path('app/pokemons.json');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->line('');
            $this->warn('Please place your pokemons.json file in: ' . storage_path('app/pokemons.json'));
            $this->line('Or provide a custom path: php artisan pokemon:import-dump /path/to/pokemons.json');
            return Command::FAILURE;
        }

        $this->info("📂 Loading file: {$filePath}");
        
        // Load and parse JSON
        $jsonContent = file_get_contents($filePath);
        $pokemons = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('❌ Invalid JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $this->info("✅ JSON loaded successfully");
        $this->info("📊 Found " . count($pokemons) . " Pokemon in dump");
        $this->line('');

        // Confirm before proceeding
        if (!$this->confirm('Do you want to start importing? This may take a few minutes.', true)) {
            $this->info('Import cancelled.');
            return Command::SUCCESS;
        }

        $this->line('');
        $this->info('🔄 Starting import...');
        $this->line('');

        $bar = $this->output->createProgressBar(count($pokemons));
        $bar->start();

        // Process each Pokemon
        foreach ($pokemons as $pokemonData) {
            try {
                $this->processPokemon($pokemonData);
                $this->stats['total']++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("\n❌ Error processing Pokemon ID {$pokemonData['id']}: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        // Show summary
        $this->showSummary();

        return Command::SUCCESS;
    }

    /**
     * Process a single Pokemon from the JSON data
     */
    protected function processPokemon(array $data): void
    {
        DB::beginTransaction();

        try {
            // Transform and create/update Pokemon
            $pokemon = $this->createOrUpdatePokemon($data);
            
            // Process related data
            $this->processTypes($pokemon, $data['types'] ?? []);
            $this->processAbilities($pokemon, $data['abilities'] ?? []);
            $this->processStats($pokemon, $data['stats'] ?? []);
            $this->processMoves($pokemon, $data['moves'] ?? []);
            
            DB::commit();

            if ($pokemon->wasRecentlyCreated) {
                $this->stats['created']++;
            } else {
                $this->stats['updated']++;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create or update a Pokemon record
     */
    protected function createOrUpdatePokemon(array $data): Pokemon
    {
        $sprites = $data['sprites'] ?? [];
        
        // Flatten sprites - we only want the default ones, not other/generations
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
        foreach ($moves as $moveData) {
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

        // Sync in chunks to avoid memory issues with 151 Pokemon
        if (!empty($syncData)) {
            $pokemon->moves()->sync($syncData);
        }
    }

    /**
     * Show import summary
     */
    protected function showSummary(): void
    {
        $this->info('====================================');
        $this->info('         IMPORT SUMMARY');
        $this->info('====================================');
        $this->line('');
        $this->info("📊 Total processed:   {$this->stats['total']}");
        $this->info("✅ Created:           {$this->stats['created']}");
        $this->info("🔄 Updated:           {$this->stats['updated']}");
        
        if ($this->stats['skipped'] > 0) {
            $this->warn("⏭️  Skipped:           {$this->stats['skipped']}");
        }
        
        if ($this->stats['errors'] > 0) {
            $this->error("❌ Errors:            {$this->stats['errors']}");
        }
        
        $this->line('');
        
        // Show counts from database
        $this->info('📈 Database counts after import:');
        $this->line('   Pokemon:  ' . Pokemon::count());
        $this->line('   Types:    ' . Type::count());
        $this->line('   Abilities: ' . Ability::count());
        $this->line('   Moves:    ' . Move::count());
        $this->line('   Stats:    ' . Stat::count());
        $this->line('');
        
        if ($this->stats['errors'] === 0) {
            $this->info('🎉 Import completed successfully!');
        } else {
            $this->warn('⚠️  Import completed with errors. Check the logs above.');
        }
        
        $this->info('====================================');
    }
}