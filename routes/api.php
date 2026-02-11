<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PokemonController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V2\PokemonController as V2PokemonController;

Route::prefix('v1')->group(function () {
    Route::get('/pokemons', [PokemonController::class, 'index']);
    Route::get('/pokemons/{id}', [PokemonController::class, 'show']);

    // Search endpoint
    Route::get('/search', [SearchController::class, 'search']);

    // Protected Team routes with hardcoded token
    Route::middleware('team.auth')->group(function () {
        Route::get('/teams', [TeamController::class, 'index']);
        Route::post('/teams', [TeamController::class, 'store']);
        Route::get('/teams/{team}', [TeamController::class, 'show']);
        Route::post('/teams/{team}', [TeamController::class, 'setPokemons']);
    });
});

Route::prefix('v2')->group(function () {
    Route::get('/pokemons', [V2PokemonController::class, 'index']);
});