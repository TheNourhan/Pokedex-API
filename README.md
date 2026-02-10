# Pokedex-API

A RESTful Pokédex API built with Laravel that lets you browse, search, and manage teams of the original 151 Pokémon.

## Table of Contents
- [Features](#features)
- [System Overview](#system-overview)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
    - [Manual Installation](#manual-installation)
    - [Docker Installation](#docker-installation)
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
- **Framework**: Laravel
- PHP (V8.2.12)
- Composer (V2.9.5)
- MySQL

## Installation

### Manual Installation
### Docker Installation

## API Documentation

## Testing

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
