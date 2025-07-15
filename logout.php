<?php
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the logout message
    session_start();
    $_SESSION['logout_message'] = 'You have been successfully logged out.';
}

// Redirect to home page
header("Location: index.php");
exit();
?>