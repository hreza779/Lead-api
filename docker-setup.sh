#!/bin/bash

# Lead API - Docker Setup Script (with External Nginx)
# This script sets up the Docker environment for the Lead API

set -e

echo "🚀 Lead API - Docker Setup (External Nginx Mode)"
echo "================================================"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"
echo ""

# Step 1: Create shared network if it doesn't exist
echo "🌐 Step 1: Creating shared network..."
if docker network inspect nginx_network >/dev/null 2>&1; then
    echo "✅ nginx_network already exists"
else
    docker network create nginx_network
    echo "✅ Created nginx_network"
fi
echo ""

# Step 2: Copy environment file
echo "📝 Step 2: Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.docker .env
    echo "✅ Created .env file from .env.docker"
else
    echo "⚠️  .env file already exists, skipping..."
fi
echo ""

# Step 3: Build and start containers
echo "🏗️  Step 3: Building Docker containers..."
docker-compose build
echo "✅ Docker containers built successfully"
echo ""

echo "🚀 Step 4: Starting services..."
docker-compose up -d
echo "✅ Services started successfully"
echo ""

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Step 5: Install dependencies
echo "📦 Step 5: Installing PHP dependencies..."
docker-compose exec -T app composer install
echo "✅ Dependencies installed"
echo ""

# Step 6: Generate application key
echo "🔑 Step 6: Generating application key..."
docker-compose exec -T app php artisan key:generate
echo "✅ Application key generated"
echo ""

# Step 7: Run migrations
echo "🗄️  Step 7: Running database migrations..."
docker-compose exec -T app php artisan migrate --force
echo "✅ Migrations completed"
echo ""

# Step 8: Generate API documentation
echo "📚 Step 8: Generating API documentation..."
docker-compose exec -T app php artisan l5-swagger:generate
echo "✅ API documentation generated"
echo ""

# Final status
echo "================================================"
echo "✅ Setup completed successfully!"
echo ""
echo "⚠️  IMPORTANT NEXT STEPS:"
echo "  1. Connect your nginx container to nginx_network:"
echo "     docker network connect nginx_network <your_nginx_container>"
echo ""
echo "  2. Configure your nginx with the provided config:"
echo "     See NGINX_SETUP.md for detailed instructions"
echo ""
echo "  3. Your app container is accessible at: lead_app:9000"
echo ""
echo "Other services:"
echo "  - Mailhog: http://localhost:8025"
echo "  - MySQL: localhost:3306"
echo "  - Redis: localhost:6379"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop services: docker-compose down"
echo "  - Access shell: docker-compose exec app bash"
echo ""
echo "📖 Read NGINX_SETUP.md for complete nginx integration guide"
echo ""
echo "Happy coding! 🎉"
