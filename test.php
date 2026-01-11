<?php
// Simple test file to check if PHP is working
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
try {
    require_once 'config/config.php';
    echo "Config loaded successfully!<br>";

    require_once 'config/database.php';
    echo "Database class loaded!<br>";

    $db = getDB();
    echo "Database connection successful!<br>";

    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "<br>";

    echo "<br><strong style='color: green;'>✓ Everything is working correctly!</strong>";

} catch (Exception $e) {
    echo "<br><strong style='color: red;'>✗ Error: " . $e->getMessage() . "</strong>";
}
