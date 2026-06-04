<?php
/**
 * Database configuration and connection management
 * UPDATED: Added complete CRUD helper methods
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
    
    // ========== NEW CRUD HELPER METHODS ==========
    
    /**
     * CREATE - Insert new record
     * @param string $table - Table name
     * @param array $data - Associative array of column => value
     * @return int|false - Last insert ID or false on failure
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
     * @param string $table - Table name
     * @param array $conditions - Associative array of column => value (optional)
     * @param string $orderBy - ORDER BY clause (optional)
     * @param string $limit - LIMIT clause (optional)
     * @return array - Array of records
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
     * READ One - Select single record by ID
     * @param string $table - Table name
     * @param int $id - Record ID
     * @param string $idColumn - ID column name (default 'id')
     * @return array|false - Record or false
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
     * @param string $table - Table name
     * @param int $id - Record ID
     * @param array $data - Associative array of column => value
     * @param string $idColumn - ID column name (default 'id')
     * @return bool - Success or failure
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
     * @param string $table - Table name
     * @param int $id - Record ID
     * @param string $idColumn - ID column name (default 'id')
     * @return bool - Success or failure
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
     * @param string $table - Table name
     * @param array $conditions - Associative array of column => value (optional)
     * @return int - Record count
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
     * @param string $table - Table name
     * @param array $columns - Columns to search in
     * @param string $keyword - Search keyword
     * @return array - Search results
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

// Session management (unchanged)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

function regenerateSession() {
    session_regenerate_id(true);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>