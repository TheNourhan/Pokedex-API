# Pokedex-API

A RESTful Pokédex API built with Laravel that lets you browse, search, and manage teams of the original 151 Pokémon.

## Table of Contents
- [Features](#features)
- [System Overview](#system-overview)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
    - [Manual Installation](#manual-installation)
    - [Docker Installation](#docker-installation)
- [Usage & Commands](#usage--commands)
  - [Server & Cache](#server--cache)
  - [Database & Migrations](#database--migrations)
  - [Import Commands](#import-commands)
- [API Documentation](#api-documentation)
- [Testing](#testing)
- [Contributing](#contributing)

## Features
- ✅ Browse and search Pokémon
- ✅ Detailed Pokémon information (stats, moves, abilities, types)
- ✅ Team management (CRUD operations, max 6 Pokémon per team)
- ✅ Import individual Pokémon from PokeAPI
- ✅ Import Pokémon from JSON dump

## System Overview

### ERD
<p style="text-align: center; border: 2px solid black; display: inline-block;">
  <img src="public/docs/ERD.png" alt="ERD" />
</p>

### Tables
#### Main Tables:
1. **pokemons** - Core Pokémon data
2. **types** - Pokémon types like fire, water, grass
3. **abilities** - Pokémon abilities
4. **moves** - Pokémon moves/attacks
5. **stats** - HP, Attack, Defense, etc.
6. **teams** - User-created teams

#### Pivot Tables:
1. **pokemon_types** - Links Pokémon to their types (1-2 types each)
2. **pokemon_abilities** - Links Pokémon to abilities (includes hidden abilities)
3. **pokemon_moves** - Links Pokémon to all learnable moves
4. **pokemon_stats** - Links Pokémon to their 6 stats (HP, Attack, etc.)
5. **team_pokemons** - Links teams to their Pokémon (max 6)
    - Each team can have maximum 6 Pokémon
    - Each Pokémon can only appear once per team
    - Same Pokémon can be in unlimited teams

## Tech Stack
- **Framework**: Laravel 11.x
- **PHP**: 8.2.12+
- **Composer**: 2.5.0+
- **Database**: MySQL 8.0+

## Installation
-  Clone the repository:
```bash
git clone https://github.com/TheNourhan/Pokedex-API.git
cd pokedex-api
```
### Manual Installation
1. Install PHP dependencies:
```bash
composer install
```
2. Copy environment file: 
```bash
cp .env.example .env
```
3. Generate application key:
```bash
php artisan key:generate
```
4. Configure your database in .env file:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pokedex
DB_USERNAME=root
DB_PASSWORD=
```
5. Run migrations:
```bash
php artisan migrate
```
6. Start the development server:
```bash
php artisan serve
```
### Docker Installation
_TODO_
## Usage & Commands
### **Server & Cache**
```bash
# Start development server
php artisan serve

# Clear all cache
php artisan optimize:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# List all registered routes
php artisan route:list
```
### **Database & Migrations**
```bash
# Run migrations
php artisan migrate

# Fresh migrations (drop all tables and re-run)
php artisan migrate:fresh

# Fresh migrations with seeders
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Create a new migration
php artisan make:migration create_table_name

# Create a new seeder
php artisan make:seeder PokemonSeeder

# Run specific seeder
php artisan db:seed --class=PokemonSeeder
```
### **Import Commands**
📥 **_Import Pokémon from JSON Dump (All 151)_**

```bash
# Place your pokemons.json file in storage/app/
# Then run:
php artisan pokemon:import-dump

# With custom file path:
php artisan pokemon:import-dump /path/to/your/pokemons.json
```
**What this does**: <br>
- ✅ Imports all 151 Pokémon with complete data
- ✅ Creates/updates types, abilities, moves, stats
- ✅ Sets up all relationships (many-to-many)
- ✅ Shows progress bar and import statistics
<br>

🌐 **_Import Single Pokémon from PokeAPI_**
```bash
# Import by ID
php artisan pokemon:import-from-api 25

# Import by name
php artisan pokemon:import-from-api pikachu
```
**What this does**: <br>
- ✅ Fetches data from PokeAPI ([docs](https://pokeapi.co/docs/v2#pokemon))
- ✅ Transforms API response to match database schema
- ✅ Automatically creates related records (types, abilities, etc.)
## API Documentation
- Team routes are protected with a hardcoded Bearer token:
```bash
# Set your custom token in .env
TEAM_AUTH_TOKEN=your-super-secret-token-2026

# Default token (if not set)
pokemon-master-2026
```
**Usage**:
```bash
Authorization: Bearer your-super-secret-token-2026
# or
Authorization: pokemon-master-2026
```
## Testing
_TODO_
## Contributing
Thank you for considering contributing to this project!

### Commit Message Convention
This project follows a simple and consistent commit message convention to keep the Git history clean, readable, and easy to navigate.

### Format:
`<type>: <short description>`

### Commit Types:
- feat: A new feature
- fix: A bug fix
- docs: Documentation only changes
- refactor: Code changes that neither fix a bug nor add a feature
- cleanup: Removing unused code, formatting, or minor improvements
- style: Code style changes (spacing, formatting, no logic changes)
- test: Adding or updating tests
- chore: Tooling, config, or dependency updates
