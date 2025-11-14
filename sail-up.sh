#!/bin/bash

# Laravel Sail Startup Script
# This script helps start Laravel Sail with proper configuration

echo "🚢 Starting Laravel Sail..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop first."
    exit 1
fi

# Check if the path is shared (basic check)
PROJECT_PATH="/media/kanishka/HDD/Workplace/orders-kpi"

# Try to start Sail and capture output
set +e  # Temporarily disable exit on error to capture output
SAIL_OUTPUT=$(./vendor/bin/sail up -d 2>&1)
SAIL_EXIT=$?
set -e  # Re-enable exit on error

if [ $SAIL_EXIT -ne 0 ]; then
    # Check if it's a file sharing error
    if echo "$SAIL_OUTPUT" | grep -q "not shared from the host"; then
        echo ""
        echo "⚠️  Docker Desktop file sharing issue detected!"
        echo ""
        
        # Check if path is in settings but Docker needs restart
        if grep -q "$PROJECT_PATH" ~/.docker/desktop/settings-store.json 2>/dev/null; then
            echo "✅ Path is configured in Docker Desktop settings, but Docker Desktop needs to be restarted."
            echo ""
            echo "Please restart Docker Desktop:"
            echo "1. Open Docker Desktop"
            echo "2. Click on the Docker icon in the menu bar"
            echo "3. Select 'Restart' or 'Quit Docker Desktop' and start it again"
            echo ""
            echo "After restarting, run this script again:"
            echo "   ./sail-up.sh"
        else
            echo "To fix this, please run:"
            echo "   ./configure-docker-sharing.sh"
            echo ""
            echo "Or manually:"
            echo "1. Open Docker Desktop"
            echo "2. Go to Settings (⚙️) → Resources → File Sharing"
            echo "3. Click '+ Add' and add this path:"
            echo "   $PROJECT_PATH"
            echo "4. Click 'Apply & Restart'"
        fi
        echo ""
    elif echo "$SAIL_OUTPUT" | grep -q "address already in use"; then
        echo ""
        echo "⚠️  Port conflict detected!"
        echo ""
        echo "A port required by Laravel Sail is already in use."
        echo ""
        if echo "$SAIL_OUTPUT" | grep -q "port.*80"; then
            echo "Port 80 is in use. You can change the app port by setting APP_PORT in .env"
            echo "For example: APP_PORT=8080"
            echo ""
            echo "Current APP_PORT setting:"
            grep "^APP_PORT" .env 2>/dev/null || echo "  (not set, defaulting to 80)"
        elif echo "$SAIL_OUTPUT" | grep -q "port.*3306"; then
            echo "Port 3306 is in use. You can change the database port by setting FORWARD_DB_PORT in .env"
            echo "For example: FORWARD_DB_PORT=3307"
            echo ""
            echo "Current FORWARD_DB_PORT setting:"
            grep "^FORWARD_DB_PORT" .env 2>/dev/null || echo "  (not set, defaulting to 3306)"
        fi
        echo ""
        echo "After updating .env, run this script again:"
        echo "   ./sail-up.sh"
        echo ""
    else
        # Some other error occurred
        echo ""
        echo "❌ Error starting Laravel Sail:"
        echo "$SAIL_OUTPUT"
        echo ""
    fi
    exit 1
fi

# Show any warnings but continue
if echo "$SAIL_OUTPUT" | grep -q "WARN"; then
    echo "$SAIL_OUTPUT" | grep "WARN"
fi

echo "✅ Laravel Sail started successfully!"
echo ""
echo "📊 Container status:"
./vendor/bin/sail ps

echo ""
# Extract ports from .env or show default
APP_PORT=$(grep "^APP_PORT=" .env 2>/dev/null | cut -d'=' -f2 || echo "80")
DB_PORT=$(grep "^FORWARD_DB_PORT=" .env 2>/dev/null | cut -d'=' -f2 || echo "3306")

echo "🌐 Application is available at: http://localhost:${APP_PORT:-80}"
echo "📦 MySQL is available on port: ${DB_PORT:-3306}"
echo ""
echo "Useful commands:"
echo "  ./vendor/bin/sail down     - Stop containers"
echo "  ./vendor/bin/sail artisan  - Run artisan commands"
echo "  ./vendor/bin/sail shell    - Access container shell"

