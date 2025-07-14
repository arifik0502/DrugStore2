<?php
// api/auth.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

switch($method) {
    case 'POST':
        if($path === '/register') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if(empty($data['username']) || empty($data['email']) || empty($data['password']) || 
               empty($data['first_name']) || empty($data['last_name'])) {
                sendResponse(['error' => 'Missing required fields'], 400);
            }
            
            $user_id = $auth->register(
                $data['username'],
                $data['email'],
                $data['password'],
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $data['address'] ?? null
            );
            
            if($user_id) {
                sendResponse(['message' => 'User registered successfully', 'user_id' => $user_id]);
            } else {
                sendResponse(['error' => 'Registration failed'], 400);
            }
        }
        elseif($path === '/login') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if(empty($data['username']) || empty($data['password'])) {
                sendResponse(['error' => 'Username and password required'], 400);
            }
            
            $result = $auth->login($data['username'], $data['password']);
            
            if($result) {
                sendResponse([
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $result['user']['id'],
                        'username' => $result['user']['username'],
                        'email' => $result['user']['email'],
                        'first_name' => $result['user']['first_name'],
                        'last_name' => $result['user']['last_name']
                    ],
                    'token' => $result['token']
                ]);
            } else {
                sendResponse(['error' => 'Invalid credentials'], 401);
            }
        }
        elseif($path === '/logout') {
            $token = getBearerToken();
            
            if(!$token) {
                sendResponse(['error' => 'Token required'], 401);
            }
            
            if($auth->logout($token)) {
                sendResponse(['message' => 'Logout successful']);
            } else {
                sendResponse(['error' => 'Logout failed'], 400);
            }
        }
        break;
        
    case 'GET':
        if($path === '/user') {
            $token = getBearerToken();
            
            if(!$token) {
                sendResponse(['error' => 'Token required'], 401);
            }
            
            $user = $auth->getUserByToken($token);
            
            if($user) {
                sendResponse(['user' => $user]);
            } else {
                sendResponse(['error' => 'Invalid token'], 401);
            }
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>