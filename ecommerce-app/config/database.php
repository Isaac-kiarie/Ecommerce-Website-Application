<?php
/**
 * Database configuration and connection management
 * UPDATED: Added complete CRUD helper methods and role-based session management
 */

class Database {
    private $host = "localhost";
    private $db_name = "ecommerce_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
    
    /**
     * CREATE - Insert new record
     */
    public function create($table, $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($query);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch(PDOException $e) {
            error_log("CREATE Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * READ - Select records with conditions
     */
    public function read($table, $conditions = [], $orderBy = '', $limit = '') {
        try {
            $query = "SELECT * FROM $table";
            $params = [];
            
            if (!empty($conditions)) {
                $where = [];
                foreach ($conditions as $column => $value) {
                    $where[] = "$column = :$column";
                    $params[$column] = $value;
                }
                $query .= " WHERE " . implode(' AND ', $where);
            }
            
            if ($orderBy) {
                $query .= " ORDER BY $orderBy";
            }
            
            if ($limit) {
                $query .= " LIMIT $limit";
            }
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("READ Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * READ ONE - Select single record by ID
     */
    public function readOne($table, $id, $idColumn = 'id') {
        try {
            $query = "SELECT * FROM $table WHERE $idColumn = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("READ One Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * UPDATE - Update existing record
     */
    public function update($table, $id, $data, $idColumn = 'id') {
        try {
            $set = [];
            foreach ($data as $column => $value) {
                $set[] = "$column = :$column";
            }
            
            $query = "UPDATE $table SET " . implode(', ', $set) . " WHERE $idColumn = :id";
            $stmt = $this->conn->prepare($query);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("UPDATE Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * DELETE - Delete record
     */
    public function delete($table, $id, $idColumn = 'id') {
        try {
            $query = "DELETE FROM $table WHERE $idColumn = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id);
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("DELETE Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * COUNT - Get record count with conditions
     */
    public function count($table, $conditions = []) {
        try {
            $query = "SELECT COUNT(*) as total FROM $table";
            $params = [];
            
            if (!empty($conditions)) {
                $where = [];
                foreach ($conditions as $column => $value) {
                    $where[] = "$column = :$column";
                    $params[$column] = $value;
                }
                $query .= " WHERE " . implode(' AND ', $where);
            }
            
            $stmt = $this->conn->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)$result['total'];
        } catch(PDOException $e) {
            error_log("COUNT Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * SEARCH - Search across multiple columns
     */
    public function search($table, $columns, $keyword) {
        try {
            $searchTerms = [];
            foreach ($columns as $column) {
                $searchTerms[] = "$column LIKE :keyword";
            }
            
            $query = "SELECT * FROM $table WHERE " . implode(' OR ', $searchTerms);
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':keyword', "%$keyword%");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("SEARCH Error: " . $e->getMessage());
            return [];
        }
    }
}

// ========== SESSION MANAGEMENT ==========
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_start();
}

// ========== SESSION HELPER FUNCTIONS ==========

/**
 * Regenerate session ID to prevent fixation attacks
 */
function regenerateSession() {
    session_regenerate_id(true);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is customer
 */
function isCustomer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

/**
 * Get user role with default fallback
 */
function getUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Require admin access - redirect if not admin
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

/**
 * Require customer access
 */
function requireCustomer() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// ========== UTILITY FUNCTIONS ==========

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get user role badge HTML
 */
function getUserRoleBadge($role) {
    switch($role) {
        case 'admin':
            return '<span style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">👑 Admin</span>';
        case 'customer':
            return '<span style="background: #3498db; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">👤 Customer</span>';
        default:
            return '<span style="background: #95a5a6; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem;">👋 Guest</span>';
    }
}

/**
 * Display success message
 */
function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Display error message
 */
function displayError($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message) . '</div>';
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get current page URL
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}
?>