<?php
// api/products.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

class ProductAPI {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function searchProducts($query, $category_id = null, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND p.name LIKE ?";
        
        $params = ['%' . $query . '%'];
        
        if($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY p.name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCategories() {
        $sql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFeaturedProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductById($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getProductsByCategory($category_id, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.is_active = 1 
                ORDER BY p.name 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$category_id, $limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$productAPI = new ProductAPI($db);

switch($method) {
    case 'GET':
        if($path === '/search') {
            $query = $_GET['q'] ?? '';
            $category_id = $_GET['category_id'] ?? null;
            $limit = min(50, $_GET['limit'] ?? 20);
            $offset = $_GET['offset'] ?? 0;
            
            if(empty($query)) {
                sendResponse(['error' => 'Search query required'], 400);
            }
            
            $products = $productAPI->searchProducts($query, $category_id, $limit, $offset);
            sendResponse(['products' => $products]);
        }
        elseif($path === '/categories') {
            $categories = $productAPI->getCategories();
            sendResponse(['categories' => $categories]);
        }
        elseif($path === '/featured') {
            $limit = min(20, $_GET['limit'] ?? 8);
            $products = $productAPI->getFeaturedProducts($limit);
            sendResponse(['products' => $products]);
        }
        elseif(preg_match('/\/(\d+)$/', $path, $matches)) {
            $product_id = $matches[1];
            $product = $productAPI->getProductById($product_id);
            
            if($product) {
                sendResponse(['product' => $product]);
            } else {
                sendResponse(['error' => 'Product not found'], 404);
            }
        }
        elseif($path === '/category') {
            $category_id = $_GET['category_id'] ?? null;
            $limit = min(50, $_GET['limit'] ?? 20);
            $offset = $_GET['offset'] ?? 0;
            
            if(!$category_id) {
                sendResponse(['error' => 'Category ID required'], 400);
            }
            
            $products = $productAPI->getProductsByCategory($category_id, $limit, $offset);
            sendResponse(['products' => $products]);
        }
        else {
            // Default: get all products
            $products = $productAPI->getFeaturedProducts(20);
            sendResponse(['products' => $products]);
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>