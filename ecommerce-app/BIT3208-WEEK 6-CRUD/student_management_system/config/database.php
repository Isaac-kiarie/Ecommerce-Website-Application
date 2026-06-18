<?php
// =============================================
// Demonstration 2: PHP Database Connection
// =============================================

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_NAME', 'student_management');

/**
 * Function to establish database connection
 * @return mysqli Connection object
 */
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Database Connection Error: " . $e->getMessage());
    }
}

/**
 * Function to test database connection (Demonstration 2)
 * @return bool True if connection successful
 */
function testConnection() {
    try {
        $conn = getConnection();
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745;'>";
        echo "<strong>✅ Demonstration 2: Database Connection Successful!</strong><br>";
        echo "Connected to MySQL Server version: " . $conn->server_info . "<br>";
        echo "Database: " . DB_NAME . "<br>";
        echo "Host: " . DB_HOST;
        echo "</div>";
        $conn->close();
        return true;
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
        echo "<strong>❌ Connection Failed!</strong><br>";
        echo "Error: " . $e->getMessage();
        echo "</div>";
        return false;
    }
}
?>