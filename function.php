<?php
// functions.php - Complete utility functions for Apochetary Cartel
/**
 * Sanitize input to prevent XSS attacks
 * @param string $input
 * @return string
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format price with currency symbol
 * @param float $price
 * @param string $currency
 * @return string
 */
function formatPrice($price, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥'
    ];
    
    $symbol = $symbols[$currency] ?? '$';
    return $symbol . number_format($price, 2);
}

/**
 * Redirect to a specific URL
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Generate a secure random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Log error messages to file
 * @param string $message
 * @param string $level
 */
function logError($message, $level = 'ERROR') {
    $logFile = 'logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Handle and log exceptions
 * @param Exception $e
 * @param bool $showUser
 */
function handleException($e, $showUser = false) {
    $errorMessage = "Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    logError($errorMessage, 'EXCEPTION');
    
    if ($showUser) {
        echo "<div class='alert alert-error'>An error occurred. Please try again later.</div>";
    }
}

/**
 * Display success message
 * @param string $message
 */
function showSuccess($message) {
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> {$message}</div>";
}

/**
 * Display error message
 * @param string $message
 */
function showError($message) {
    echo "<div class='alert alert-error'><i class='fas fa-exclamation-triangle'></i> {$message}</div>";
}

/**
 * Session management class
 */
class Session {
    
    /**
     * Start session if not already started
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Set session variable
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Remove session variable
     * @param string $key
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get user ID
     * @return int|null
     */
    public static function getUserId() {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get username
     * @return string|null
     */
    public static function getUsername() {
        self::start();
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Login user
     * @param int $userId
     * @param string $username
     * @param array $additionalData
     */
    public static function login($userId, $username, $additionalData = []) {
        self::start();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Set additional user data
        foreach ($additionalData as $key => $value) {
            $_SESSION[$key] = $value;
        }
        
        logError("User login: {$username} (ID: {$userId})", 'INFO');
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::start();
        
        $username = self::getUsername();
        $userId = self::getUserId();
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        logError("User logout: {$username} (ID: {$userId})", 'INFO');
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }
    
    /**
     * Check if session is expired
     * @param int $timeout Session timeout in seconds (default: 30 minutes)
     * @return bool
     */
    public static function isExpired($timeout = 1800) {
        self::start();
        
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        if (time() - $_SESSION['last_activity'] > $timeout) {
            return true;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::start();
        session_destroy();
    }
    
    /**
     * Get user role
     * @return string|null
     */
    public static function getUserRole() {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Check if user has specific role
     * @param string $role
     * @return bool
     */
    public static function hasRole($role) {
        return self::getUserRole() === $role;
    }
    
    /**
     * Get user email
     * @return string|null
     */
    public static function getUserEmail() {
        self::start();
        return $_SESSION['user_email'] ?? null;
    }
}

/**
 * Cart management functions
 */
class Cart {
    
    /**
     * Add item to cart
     * @param array $item
     */
    public static function addItem($item) {
        Session::start();
        
        $cart = Session::get('cart', []);
        $productId = $item['product_id'];
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $item['quantity'];
        } else {
            $cart[$productId] = $item;
        }
        
        Session::set('cart', $cart);
    }
    
    /**
     * Remove item from cart
     * @param int $productId
     */
    public static function removeItem($productId) {
        Session::start();
        
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::set('cart', $cart);
        }
    }
    
    /**
     * Update item quantity
     * @param int $productId
     * @param int $quantity
     */
    public static function updateQuantity($productId, $quantity) {
        Session::start();
        
        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
            }
            Session::set('cart', $cart);
        }
    }
    
    /**
     * Get cart items
     * @return array
     */
    public static function getItems() {
        Session::start();
        return Session::get('cart', []);
    }
    
    /**
     * Get cart total
     * @return float
     */
    public static function getTotal() {
        $cart = self::getItems();
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }
    
    /**
     * Get cart item count
     * @return int
     */
    public static function getItemCount() {
        $cart = self::getItems();
        $count = 0;
        
        foreach ($cart as $item) {
            $count += $item['quantity'];
        }
        
        return $count;
    }
    
    /**
     * Clear cart
     */
    public static function clear() {
        Session::remove('cart');
    }
    
    /**
     * Check if cart is empty
     * @return bool
     */
    public static function isEmpty() {
        $cart = self::getItems();
        return empty($cart);
    }
}

/**
 * Dummy payment processing functions
 */
class DummyPayment {
    
    /**
     * Process QR code payment (dummy implementation)
     * @param array $paymentData
     * @return array
     */
    public static function processQRPayment($paymentData) {
        // Simulate processing delay
        sleep(2);
        
        // Random success/failure for demo
        $success = rand(1, 10) > 2; // 80% success rate
        
        $result = [
            'success' => $success,
            'transaction_id' => 'TXN' . date('YmdHis') . rand(1000, 9999),
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'USD',
            'timestamp' => date('Y-m-d H:i:s'),
            'payment_method' => 'QR Code'
        ];
        
        if ($success) {
            $result['message'] = 'Payment processed successfully';
            $result['status'] = 'completed';
        } else {
            $result['message'] = 'Payment failed. Please try again.';
            $result['status'] = 'failed';
            $result['error_code'] = 'PAYMENT_DECLINED';
        }
        
        // Log payment attempt
        logError("Payment attempt: " . json_encode($result), 'INFO');
        
        return $result;
    }
    
    /**
     * Verify payment status (dummy implementation)
     * @param string $transactionId
     * @return array
     */
    public static function verifyPayment($transactionId) {
        // Simulate verification
        $statuses = ['pending', 'completed', 'failed', 'cancelled'];
        $status = $statuses[array_rand($statuses)];
        
        return [
            'transaction_id' => $transactionId,
            'status' => $status,
            'verified_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate payment QR code data
     * @param array $orderData
     * @return string
     */
    public static function generateQRData($orderData) {
        $qrData = [
            'order_id' => $orderData['order_id'],
            'merchant_name' => 'Apochetary Cartel',
            'merchant_id' => 'APOC001',
            'amount' => $orderData['amount'],
            'currency' => $orderData['currency'] ?? 'USD',
            'description' => 'Pharmacy Order Payment',
            'timestamp' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
        ];
        
        return json_encode($qrData);
    }
    
    /**
     * Refund payment (dummy implementation)
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public static function refundPayment($transactionId, $amount) {
        $success = rand(1, 10) > 1; // 90% success rate
        
        $result = [
            'success' => $success,
            'refund_id' => 'REF' . date('YmdHis') . rand(1000, 9999),
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($success) {
            $result['message'] = 'Refund processed successfully';
            $result['status'] = 'completed';
        } else {
            $result['message'] = 'Refund failed. Please contact support.';
            $result['status'] = 'failed';
        }
        
        logError("Refund attempt: " . json_encode($result), 'INFO');
        
        return $result;
    }
}

/**
 * Order management functions
 */
class Order {
    
    /**
     * Generate order ID
     * @param int $userId
     * @return string
     */
    public static function generateOrderId($userId) {
        return 'ORD' . date('YmdHis') . str_pad($userId, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate order totals
     * @param array $cartItems
     * @param array $shippingInfo
     * @return array
     */
    public static function calculateTotals($cartItems, $shippingInfo = []) {
        $subtotal = 0;
        
        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $taxRate = 0.08; // 8% tax
        $taxAmount = $subtotal * $taxRate;
        
        $shippingFee = 0;
        if ($subtotal < 50) {
            $shippingFee = 9.99;
        }
        
        $total = $subtotal + $taxAmount + $shippingFee;
        
        return [
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'shipping_fee' => $shippingFee,
            'total' => $total
        ];
    }
    
    /**
     * Create pending order
     * @param array $orderData
     * @return array
     */
    public static function createPendingOrder($orderData) {
        $pendingOrder = [
            'cart_items' => $orderData['cart_items'],
            'shipping_address' => $orderData['shipping_address'],
            'billing_address' => $orderData['billing_address'] ?? $orderData['shipping_address'],
            'totals' => self::calculateTotals($orderData['cart_items']),
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ];
        
        // Add totals to main array for easier access
        $pendingOrder = array_merge($pendingOrder, $pendingOrder['totals']);
        
        Session::set('pending_order', $pendingOrder);
        
        return $pendingOrder;
    }
    
    /**
     * Get pending order
     * @return array|null
     */
    public static function getPendingOrder() {
        return Session::get('pending_order');
    }
    
    /**
     * Clear pending order
     */
    public static function clearPendingOrder() {
        Session::remove('pending_order');
    }
}

/**
 * Utility functions
 */

/**
 * Calculate shipping cost
 * @param float $subtotal
 * @param string $shippingMethod
 * @return float
 */
function calculateShipping($subtotal, $shippingMethod = 'standard') {
    if ($subtotal >= 50) {
        return 0; // Free shipping over $50
    }
    
    switch ($shippingMethod) {
        case 'express':
            return 19.99;
        case 'overnight':
            return 29.99;
        case 'standard':
        default:
            return 9.99;
    }
}

/**
 * Validate prescription requirement
 * @param array $cartItems
 * @return array Items that require prescription
 */
function validatePrescriptions($cartItems) {
    $prescriptionRequired = [];
    
    foreach ($cartItems as $item) {
        if ($item['requires_prescription'] ?? false) {
            $prescriptionRequired[] = $item;
        }
    }
    
    return $prescriptionRequired;
}

/**
 * Generate random order status for demo
 * @return string
 */
function getDemoOrderStatus() {
    $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    return $statuses[array_rand($statuses)];
}

/**
 * Format phone number
 * @param string $phone
 * @return string
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($phone, 0, 3),
            substr($phone, 3, 3),
            substr($phone, 6, 4)
        );
    }
    
    return $phone;
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

/**
 * Generate dummy tracking number
 * @return string
 */
function generateTrackingNumber() {
    return 'AC' . strtoupper(substr(uniqid(), -8));
}

/**
 * Check if file upload is valid
 * @param array $file $_FILES array element
 * @param array $allowedTypes
 * @param int $maxSize Max size in bytes
 * @return bool
 */
function isValidUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($extension, $allowedTypes);
}

// Error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorMessage = "Error: {$message} in {$file} on line {$line}";
    logError($errorMessage, 'PHP_ERROR');
    
    return true;
});

// Exception handler
set_exception_handler(function($exception) {
    handleException($exception, true);
});

// Start session automatically
Session::start();

// Check for session timeout
if (Session::isLoggedIn() && Session::isExpired()) {
    Session::logout();
    redirect('login.php?timeout=1');
}
?>