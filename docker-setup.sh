#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🐳 Setting up Pokedex API Docker environment...${NC}"

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

# Remind user to update .env
echo -e "${YELLOW}⚠️  Make sure your .env file has Docker database configuration:${NC}"
echo -e "${YELLOW}   DB_HOST=mysql${NC}"
echo -e "${YELLOW}   DB_USERNAME=pokedex${NC}"
echo -e "${YELLOW}   DB_PASSWORD=secret${NC}"
echo ""

# Stop and remove existing containers
echo -e "${BLUE}🛑 Stopping existing containers...${NC}"
docker-compose down -v 2>/dev/null

# Build and start containers
echo -e "${BLUE}📦 Building Docker image (first time only)...${NC}"
docker-compose up -d --build

# Wait for MySQL to be ready
echo -e "${BLUE}⏳ Waiting for MySQL to be ready...${NC}"
sleep 15

# Install Composer dependencies
echo -e "${BLUE}📦 Installing Composer dependencies...${NC}"
docker exec pokedex-app composer install --no-interaction

# Generate application key
echo -e "${BLUE}🔑 Generating application key...${NC}"
docker exec pokedex-app php artisan key:generate

# Run migrations and seeders
echo -e "${BLUE}🗄️ Running database migrations and seeders...${NC}"
docker exec pokedex-app php artisan migrate:fresh --seed

# Set permissions
echo -e "${BLUE}🔧 Setting permissions...${NC}"
docker exec pokedex-app chmod -R 777 storage bootstrap/cache

echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Docker setup complete!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}🌐 Application:  http://localhost:8000${NC}"
echo -e "${GREEN}📊 Database:     mysql://pokedex:secret@localhost:3307/pokedex${NC}"
echo -e "${GREEN}🐳 Containers:   pokedex-app, pokedex-mysql${NC}"
echo ""
echo -e "${YELLOW}⚠️  To switch back to local development, update .env:${NC}"
echo -e "${YELLOW}   DB_HOST=127.0.0.1${NC}"
echo -e "${YELLOW}   DB_USERNAME=root${NC}"
echo -e "${YELLOW}   DB_PASSWORD=${NC}"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}📋 Useful Docker Commands:${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo "  docker exec pokedex-app php artisan tinker        # Tinker"
echo "  docker exec pokedex-app php artisan test          # Run tests"
echo "  docker exec pokedex-app php artisan route:list    # List routes"
echo "  docker-compose down                              # Stop containers"
echo "  docker-compose logs -f                           # View logs"
echo "  docker exec -it pokedex-mysql mysql -u pokedex -psecret pokedex  # MySQL"
echo ""
