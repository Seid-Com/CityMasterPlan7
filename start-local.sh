#!/bin/bash

# City Master Plan GIS - Local Development Server Script
# This script helps you run the application locally

echo "🚀 Starting City Master Plan GIS locally..."
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed or not in PATH"
    echo "   Please install PHP 8.1 or higher"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
echo "✅ PHP Version: $PHP_VERSION"

# Check if we're in the right directory
if [[ ! -f "spark" ]]; then
    echo "❌ Please run this script from the CodeIgniter project root directory"
    echo "   (The directory containing the 'spark' file)"
    exit 1
fi

echo "✅ CodeIgniter project found"

# Check if vendor directory exists
if [[ ! -d "vendor" ]]; then
    echo "⚠️  Composer dependencies not found"
    echo "   Running: composer install"
    composer install
fi

echo "✅ Dependencies ready"

# Create necessary directories
mkdir -p writable/cache writable/logs writable/session writable/uploads
chmod -R 755 writable/

echo "✅ Directories created"

echo ""
echo "🌐 Starting development servers..."
echo ""

# Method 1: Using CodeIgniter spark serve
echo "📍 Method 1: CodeIgniter Development Server"
echo "   Command: php spark serve --host=0.0.0.0 --port=8080"
echo "   URL: http://localhost:8080"
echo ""

# Method 2: Using PHP built-in server from public directory  
echo "📍 Method 2: PHP Built-in Server (Current setup)"
echo "   Command: cd public && php -S localhost:8080"
echo "   URL: http://localhost:8080"
echo ""

# Method 3: Alternative port
echo "📍 Method 3: Alternative port"
echo "   Command: php spark serve --host=127.0.0.1 --port=3000"
echo "   URL: http://127.0.0.1:3000"
echo ""

echo "💡 Choose your preferred method:"
echo "   1) For CodeIgniter routing: php spark serve --host=0.0.0.0 --port=8080"
echo "   2) For current setup: cd public && php -S localhost:8000"
echo ""

echo "🔧 Common issues and solutions:"
echo "   • Port in use: Change --port=XXXX to different number"
echo "   • Permission denied: Run chmod +x spark"
echo "   • Database errors: Application works in demo mode"
echo "   • Host not accessible: Use --host=0.0.0.0 instead of localhost"
echo ""

# Ask user which method to use
read -p "Start server now? (1=spark serve, 2=php -S, n=no): " choice

case $choice in
    1)
        echo "🚀 Starting with spark serve..."
        php spark serve --host=0.0.0.0 --port=8080
        ;;
    2)
        echo "🚀 Starting with PHP built-in server..."
        cd public
        php -S 0.0.0.0:8000
        ;;
    *)
        echo "ℹ️  Server not started. Use the commands above to start manually."
        ;;
esac