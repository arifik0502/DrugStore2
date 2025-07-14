<?php
// api/cart.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

class CartAPI {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function addToCart($user_id, $product_id, $quantity = 1) {
        // Check if product exists and has stock
        $product = $this->getProduct($product_id);
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return ['error' => 'Insufficient stock'];
        }
        
        // Check if item already exists in cart
        $existing = $this->getCartItem($user_id, $product_id);
        
        if ($existing) {
            // Update quantity
            $new_quantity = $existing['quantity'] + $quantity;
            if ($new_quantity > $product['stock_quantity']) {
                return ['error' => 'Insufficient stock for requested quantity'];
            }
            
            $query = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$new_quantity, $user_id, $product_id]);
        } else {
            // Insert new item
            $query = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        return ['success' => true, 'message' => 'Item added to cart'];
    }
    
    public function updateCartItem($user_id, $product_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($user_id, $product_id);
        }
        
        // Check stock
        $product = $this->getProduct($product_id);
        if (!$product) {
            return ['error' => 'Product not found'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return ['error' => 'Insufficient stock'];
        }
        
        $query = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$quantity, $user_id, $product_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Cart updated'];
        } else {
            return ['error' => 'Cart item not found'];
        }
    }
    
    public function removeFromCart($user_id, $product_id) {
        $query = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$user_id, $product_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Item removed from cart'];
        } else {
            return ['error' => 'Cart item not found'];
        }
    }
    
    public function getCart($user_id) {
        $query = "SELECT ci.*, p.name, p.price, p.requires_prescription, p.stock_quantity,
                         c.name as category_name, (ci.quantity * p.price) as subtotal
                  FROM cart_items ci
                  JOIN products p ON ci.product_id = p.id
                  JOIN categories c ON p.category_id = c.id
                  WHERE ci.user_id = ? AND p.is_active = 1
                  ORDER BY ci.added_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = 0;
        $item_count = 0;
        
        foreach ($items as &$item) {
            $total += $item['subtotal'];
            $item_count += $item['quantity'];
        }
        
        return [
            'items' => $items,
            'total' => $total,
            'item_count' => $item_count
        ];
    }
    
    public function clearCart($user_id) {
        $query = "DELETE FROM cart_items WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$user_id]);
        
        return ['success' => true, 'message' => 'Cart cleared'];
    }
    
    public function getCartCount($user_id) {
        $query = "SELECT COALESCE(SUM(quantity), 0) as count FROM cart_items WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ['count' => (int)$result['count']];
    }
    
    private function getProduct($product_id) {
        $query = "SELECT * FROM products WHERE id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getCartItem($user_id, $product_id) {
        $query = "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Verify authentication for all cart operations
$token = getBearerToken();
if (!$token) {
    sendResponse(['error' => 'Authentication required'], 401);
}

$user = $auth->getUserByToken($token);
if (!$user) {
    sendResponse(['error' => 'Invalid token'], 401);
}

$cartAPI = new CartAPI($db);
$user_id = $user['id'];

switch($method) {
    case 'POST':
        // Add to cart
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['product_id'])) {
            sendResponse(['error' => 'Product ID required'], 400);
        }
        
        $quantity = $data['quantity'] ?? 1;
        $result = $cartAPI->addToCart($user_id, $data['product_id'], $quantity);
        
        if (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse($result);
        }
        break;
        
    case 'PUT':
        // Update cart item
        if (preg_match('/\/(\d+)$/', $path, $matches)) {
            $product_id = $matches[1];
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['quantity'])) {
                sendResponse(['error' => 'Quantity required'], 400);
            }
            
            $result = $cartAPI->updateCartItem($user_id, $product_id, $data['quantity']);
            
            if (isset($result['error'])) {
                sendResponse($result, 400);
            } else {
                sendResponse($result);
            }
        } else {
            sendResponse(['error' => 'Product ID required'], 400);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/\/(\d+)$/', $path, $matches)) {
            // Remove specific item
            $product_id = $matches[1];
            $result = $cartAPI->removeFromCart($user_id, $product_id);
            
            if (isset($result['error'])) {
                sendResponse($result, 400);
            } else {
                sendResponse($result);
            }
        } elseif ($path === '/clear') {
            // Clear entire cart
            $result = $cartAPI->clearCart($user_id);
            sendResponse($result);
        } else {
            sendResponse(['error' => 'Invalid endpoint'], 400);
        }
        break;
        
    case 'GET':
        if ($path === '/count') {
            // Get cart count
            $result = $cartAPI->getCartCount($user_id);
            sendResponse($result);
        } else {
            // Get full cart
            $result = $cartAPI->getCart($user_id);
            sendResponse($result);
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>