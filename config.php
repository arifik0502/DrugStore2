<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'apochetarycartel');
define('DB_USER', 'root');
define('DB_PASS', 'toor');
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user information
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Logout function
function logout() {
    session_destroy();
    session_start();
    $_SESSION['logout_message'] = 'You have been successfully logged out.';
}

// Initialize database tables if they don't exist
function initializeDatabase() {
    try {
        $pdo = getDBConnection();
        
        // Create users table
        $createUsersTable = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'customer') DEFAULT 'customer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        // Create products table
        $createProductsTable = "
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                category VARCHAR(100),
                requires_prescription BOOLEAN DEFAULT FALSE,
                stock_quantity INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        
        // Create orders table
        $createOrdersTable = "
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                total_amount DECIMAL(10, 2) NOT NULL,
                status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                shipping_address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        // Create order_items table
        $createOrderItemsTable = "
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ";
        
        // Create cart table
        $createCartTable = "
            CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_product (user_id, product_id)
            )
        ";
        
        // Create prescriptions table
        $createPrescriptionsTable = "
            CREATE TABLE IF NOT EXISTS prescriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                prescription_number VARCHAR(100) UNIQUE NOT NULL,
                doctor_name VARCHAR(255),
                prescribed_date DATE,
                status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                prescription_file VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        // Execute table creation
        $pdo->exec($createUsersTable);
        $pdo->exec($createProductsTable);
        $pdo->exec($createOrdersTable);
        $pdo->exec($createOrderItemsTable);
        $pdo->exec($createCartTable);
        $pdo->exec($createPrescriptionsTable);
        
        // Insert sample products if products table is empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $productCount = $stmt->fetchColumn();
        
        if ($productCount == 0) {
            $sampleProducts = [
                ['Amoxicillin 500mg', 'Antibiotic medication for bacterial infections', 24.99, 'Antibiotics', 1, 100],
                ['Ibuprofen 200mg', 'Pain reliever and anti-inflammatory', 8.99, 'Pain Relief', 0, 200],
                ['Metformin 500mg', 'Diabetes medication for blood sugar control', 49.99, 'Diabetes Medication', 1, 150],
                ['Insulin 100u/mL', 'Diabetes treatment insulin injection', 89.99, 'Diabetes Treatment', 1, 75],
                ['Acetaminophen 500mg', 'Pain reliever and fever reducer', 6.99, 'Pain Relief', 0, 300],
                ['Aspirin 81mg', 'Low-dose aspirin for heart health', 12.99, 'Heart Health', 0, 250],
                ['Omeprazole 20mg', 'Proton pump inhibitor for acid reflux', 19.99, 'Digestive Health', 0, 180],
                ['Lisinopril 10mg', 'ACE inhibitor for blood pressure', 34.99, 'Blood Pressure', 1, 120]
            ];
            
            $insertProduct = $pdo->prepare("INSERT INTO products (name, description, price, category, requires_prescription, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($sampleProducts as $product) {
                $insertProduct->execute($product);
            }
        }
        
        // Create default admin user if no users exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        
        if ($userCount == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@apochetary.com', $adminPassword, 'admin']);
        }
        
    } catch (PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

// Call initialization function
initializeDatabase();

// Helper function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Helper function to validate password strength
function isValidPassword($password) {
    // At least 6 characters long
    return strlen($password) >= 6;
}

// Helper function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF protection functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Error logging function
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, 3, 'errors.log');
}

// Success/Error message functions
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

function getSuccessMessage() {
    $message = $_SESSION['success_message'] ?? '';
    unset($_SESSION['success_message']);
    return $message;
}

function getErrorMessage() {
    $message = $_SESSION['error_message'] ?? '';
    unset($_SESSION['error_message']);
    return $message;
}
?>