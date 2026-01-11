<?php
// Super simple test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. PHP is working!<br>";
echo "2. PHP Version: " . phpversion() . "<br>";

// Test if we can include config
echo "3. Attempting to load config...<br>";
try {
    $config_path = __DIR__ . '/config/config.php';
    echo "4. Config path: $config_path<br>";
    echo "5. Config exists: " . (file_exists($config_path) ? 'Yes' : 'No') . "<br>";

    if (file_exists($config_path)) {
        require_once $config_path;
        echo "6. Config loaded successfully!<br>";
    } else {
        echo "6. ERROR: Config file not found!<br>";
    }
} catch (Throwable $e) {
    echo "6. ERROR loading config: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br>Done!";
