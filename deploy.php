<?php
/**
 * Local Deployment Script for City Master Plan Web GIS
 * 
 * This script helps set up the application for local deployment
 * with offline capability and local database configuration.
 */

echo "=== City Master Plan Web GIS - Local Deployment Setup ===\n\n";

// Check PHP version
echo "1. Checking PHP version...\n";
$phpVersion = phpversion();
echo "   PHP version: $phpVersion\n";
if (version_compare($phpVersion, '8.1.0', '<')) {
    echo "   ⚠️  Warning: PHP 8.1+ recommended\n";
} else {
    echo "   ✅ PHP version OK\n";
}

// Check required PHP extensions
echo "\n2. Checking required PHP extensions...\n";
$requiredExtensions = ['pgsql', 'json', 'curl', 'mbstring', 'intl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext extension loaded\n";
    } else {
        echo "   ❌ $ext extension missing\n";
    }
}

// Check writable directories
echo "\n3. Checking writable directories...\n";
$writableDirectories = [
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/uploads'
];

foreach ($writableDirectories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "   ✅ Created directory: $dir\n";
    } else if (is_writable($dir)) {
        echo "   ✅ Directory writable: $dir\n";
    } else {
        echo "   ❌ Directory not writable: $dir\n";
    }
}

// Check database connection
echo "\n4. Testing database connectivity...\n";
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
if ($databaseUrl && $databaseUrl !== 'root') {
    echo "   📊 DATABASE_URL configured\n";
    
    // Try to parse the URL
    $url = parse_url($databaseUrl);
    if ($url) {
        $host = $url['host'] ?? 'localhost';
        $port = $url['port'] ?? 5432;
        $dbname = ltrim($url['path'] ?? '', '/');
        echo "   📍 Target: $host:$port/$dbname\n";
        
        // Test connection
        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $user = $url['user'] ?? 'postgres';
            $pass = $url['pass'] ?? '';
            
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
            echo "   ✅ Database connection successful\n";
            
            // Test for PostGIS
            $stmt = $pdo->query("SELECT PostGIS_Version()");
            if ($stmt) {
                $version = $stmt->fetchColumn();
                echo "   🗺️  PostGIS version: $version\n";
            }
        } catch (PDOException $e) {
            echo "   ⚠️  Database connection failed: " . $e->getMessage() . "\n";
            echo "   📝 Application will run with demo data in offline mode\n";
        }
    }
} else {
    echo "   ⚠️  No DATABASE_URL configured\n";
    echo "   📝 Application will run with demo data in offline mode\n";
}

// Check Composer dependencies
echo "\n5. Checking Composer dependencies...\n";
if (file_exists('vendor/autoload.php')) {
    echo "   ✅ Vendor directory exists\n";
    require_once 'vendor/autoload.php';
    echo "   ✅ Autoloader working\n";
} else {
    echo "   ❌ Composer dependencies not installed\n";
    echo "   💡 Run: composer install\n";
}

// Display deployment instructions
echo "\n=== Deployment Instructions ===\n";
echo "🚀 Local Development Server:\n";
echo "   cd public && php -S localhost:8080\n";
echo "   Then visit: http://localhost:8080\n\n";

echo "🌐 Production Deployment (Apache/Nginx):\n";
echo "   - Point document root to: /path/to/project/public/\n";
echo "   - Ensure .htaccess is enabled (Apache)\n";
echo "   - Configure URL rewriting for CodeIgniter\n\n";

echo "📱 Offline Mode Features:\n";
echo "   ✅ Works without internet connection\n";
echo "   ✅ Local coordinate grid basemap\n";
echo "   ✅ Demo spatial data included\n";
echo "   ✅ Full GIS functionality available\n\n";

echo "🔧 Configuration:\n";
echo "   - Database: app/Config/Database.php\n";
echo "   - Environment: .env file (create if needed)\n";
echo "   - Routes: app/Config/Routes.php\n\n";

echo "📊 Next Steps:\n";
echo "1. Configure your database connection (optional)\n";
echo "2. Run database migrations: php spark migrate\n";
echo "3. Import your spatial data\n";
echo "4. Customize map center and zoom in app/Views/map.php\n";

echo "\n✅ Setup complete! Your City Master Plan GIS is ready for local deployment.\n";
?>