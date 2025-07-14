<?php
// api/orders.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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

class OrderAPI {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function createOrder($user_id, $shipping_address) {
        $this->conn->beginTransaction();
        
        try {
            // Get cart items
            $cart = $this->getCartItems($user_id);
            
            if (empty($cart['items'])) {
                throw new Exception('Cart is empty');
            }
            
            // Validate stock availability
            foreach ($cart['items'] as $item) {
                if ($item['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for {$item['name']}");
                }
            }
            
            // Create order
            $query = "INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id, $cart['total'], $shipping_address]);
            $order_id = $this->conn->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart['items'] as $item) {
                // Insert order item
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update stock
                $query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $query = "DELETE FROM cart_items WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'order_id' => $order_id,
                'message' => 'Order created successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['error' => $e->getMessage()];
        }
    }
    
    public function getUserOrders($user_id, $limit = 20, $offset = 0) {
        $query = "SELECT o.*, 
                         (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                  FROM orders o
                  WHERE o.user_id = ?
                  ORDER BY o.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getOrderDetails($order_id, $user_id) {
        // Get order info
        $query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $query = "SELECT oi.*, p.name, p.requires_prescription, c.name as category_name
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  JOIN categories c ON p.category_id = c.id
                  WHERE oi.order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $order['items'] = $items;
        
        return $order;
    }
    
    public function updateOrderStatus($order_id, $status) {
        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $valid_statuses)) {
            return ['error' => 'Invalid status'];
        }
        
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([$status, $order_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Order status updated'];
        } else {
            return ['error' => 'Order not found'];
        }
    }
    
    private function getCartItems($user_id) {
        $query = "SELECT ci.*, p.name, p.price, p.stock_quantity,
                         (ci.quantity * p.price) as subtotal
                  FROM cart_items ci
                  JOIN products p ON ci.product_id = p.id
                  WHERE ci.user_id = ? AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total = 0;
        foreach ($items as $item) {
            $total += $item['subtotal'];
        }
        
        return [
            'items' => $items,
            'total' => $total
        ];
    }
}

// Verify authentication
$token = getBearerToken();
if (!$token) {
    sendResponse(['error' => 'Authentication required'], 401);
}

$user = $auth->getUserByToken($token);
if (!$user) {
    sendResponse(['error' => 'Invalid token'], 401);
}

$orderAPI = new OrderAPI($db);
$user_id = $user['id'];

switch($method) {
    case 'POST':
        // Create order
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['shipping_address'])) {
            sendResponse(['error' => 'Shipping address required'], 400);
        }
        
        $result = $orderAPI->createOrder($user_id, $data['shipping_address']);
        
        if (isset($result['error'])) {
            sendResponse($result, 400);
        } else {
            sendResponse($result);
        }
        break;
        
    case 'GET':
        if (preg_match('/\/(\d+)$/', $path, $matches)) {
            // Get specific order
            $order_id = $matches[1];
            $order = $orderAPI->getOrderDetails($order_id, $user_id);
            
            if ($order) {
                sendResponse(['order' => $order]);
            } else {
                sendResponse(['error' => 'Order not found'], 404);
            }
        } else {
            // Get user orders
            $limit = min(50, $_GET['limit'] ?? 20);
            $offset = $_GET['offset'] ?? 0;
            
            $orders = $orderAPI->getUserOrders($user_id, $limit, $offset);
            sendResponse(['orders' => $orders]);
        }
        break;
        
    case 'PUT':
        // Update order status (admin only for now)
        if (preg_match('/\/(\d+)\/status$/', $path, $matches)) {
            $order_id = $matches[1];
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (empty($data['status'])) {
                sendResponse(['error' => 'Status required'], 400);
            }
            
            $result = $orderAPI->updateOrderStatus($order_id, $data['status']);
            
            if (isset($result['error'])) {
                sendResponse($result, 400);
            } else {
                sendResponse($result);
            }
        } else {
            sendResponse(['error' => 'Invalid endpoint'], 400);
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>