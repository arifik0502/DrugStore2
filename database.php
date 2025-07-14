<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "apochetaryCartel";
    private $username = "root";
    private $password = "toor";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// config/auth.php
class Auth {
    private $conn;
    private $table_name = "users";
    private $tokens_table = "user_tokens";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($username, $email, $password, $first_name, $last_name, $phone = null, $address = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (username, email, password_hash, first_name, last_name, phone, address) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        if($stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone, $address])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function login($username, $password) {
        $query = "SELECT id, username, email, password_hash, first_name, last_name 
                 FROM " . $this->table_name . " 
                 WHERE (username = ? OR email = ?) AND is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username, $username]);
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password_hash'])) {
                $token = $this->generateToken($user['id']);
                return [
                    'user' => $user,
                    'token' => $token
                ];
            }
        }
        return false;
    }

    public function generateToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $query = "INSERT INTO " . $this->tokens_table . " (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        if($stmt->execute([$user_id, $token, $expires_at])) {
            return $token;
        }
        return false;
    }

    public function validateToken($token) {
        $query = "SELECT u.id, u.username, u.email, u.first_name, u.last_name 
                 FROM " . $this->tokens_table . " t
                 JOIN " . $this->table_name . " u ON t.user_id = u.id
                 WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$token]);
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function logout($token) {
        $query = "DELETE FROM " . $this->tokens_table . " WHERE token = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$token]);
    }

    public function getUserByToken($token) {
        return $this->validateToken($token);
    }
}

// Helper function to get authorization header
function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

// Helper function to get bearer token
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Response helper
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>