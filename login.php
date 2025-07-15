<?php
require_once 'config.php';

// If already logged in, redirect to main page
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login_identifier = sanitizeInput($_POST['login_identifier']);
    $password = $_POST['password'];

    if (empty($login_identifier) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Determine if login identifier is email or username
            $isEmail = isValidEmail($login_identifier);
            $query = $isEmail 
                ? "SELECT id, username, email, password_hash, role FROM users WHERE email = ?" 
                : "SELECT id, username, email, password_hash, role FROM users WHERE username = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$login_identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Redirect to intended page or home
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: " . $redirect);
                exit();
            } else {
                $error = 'Invalid username/email or password';
                logError("Failed login attempt for identifier: " . $login_identifier);
            }
        } catch(PDOException $e) {
            $error = 'Database error occurred. Please try again.';
            logError("Database error during login: " . $e->getMessage());
        }
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitizeInput($_POST['reg_username']);
    $email = sanitizeInput($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm_password = isset($_POST['reg_confirm_password']) ? $_POST['reg_confirm_password'] : '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 6 characters long';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                // Hash password and create user
                // After validation:
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) 
                      VALUES (?, ?, ?, 'customer')");
$stmt->execute([$username, $email, $hashedPassword]);
$success = 'Registration successful! You can now log in.';
                
                $success = 'Registration successful! You can now log in with your credentials.';
                logError("New user registered: " . $username . " (" . $email . ")");
            }
        } catch(PDOException $e) {
            $error = 'Database error occurred. Please try again.';
            logError("Database error during registration: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Apochetary Cartel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 2rem;
        }

        .tab-button {
            flex: 1;
            padding: 1rem;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }

        .tab-button.active {
            background: #667eea;
            color: white;
            border-bottom-color: #5a6fd8;
        }

        .tab-button:first-child {
            border-radius: 8px 0 0 8px;
        }

        .tab-button:last-child {
            border-radius: 0 8px 8px 0;
        }

        .tab-button:hover:not(.active) {
            background: #e9ecef;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:invalid {
            border-color: #dc3545;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #5a6fd8;
            text-decoration: underline;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading .btn {
            background: #ccc;
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .tab-button {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-pills"></i> Apochetary Cartel</h1>
            <p>Access your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="form-tabs">
            <button class="tab-button <?php echo !$success ? 'active' : ''; ?>" onclick="showTab('login')">Login</button>
            <button class="tab-button <?php echo $success ? 'active' : ''; ?>" onclick="showTab('register')">Register</button>
        </div>

        <!-- Login Form -->
        <div id="login-tab" class="tab-content <?php echo !$success ? 'active' : ''; ?>">
            <form method="POST" action="" onsubmit="setLoading(this)">
                <div class="form-group">
                    <label for="login_identifier">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" id="login_identifier" name="login_identifier" required 
                           value="<?php echo isset($_POST['login_identifier']) ? htmlspecialchars($_POST['login_identifier']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div id="register-tab" class="tab-content <?php echo $success ? 'active' : ''; ?>">
            <form method="POST" action="" onsubmit="setLoading(this)">
                <div class="form-group">
                    <label for="reg_username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="reg_username" name="reg_username" required 
                           minlength="3" maxlength="50" value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="reg_email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="reg_email" name="reg_email" required 
                           value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="reg_password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="reg_password" name="reg_password" required minlength="6">
                    <div class="password-requirements">Password must be at least 6 characters long</div>
                </div>
                <div class="form-group">
                    <label for="reg_confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="reg_confirm_password" name="reg_confirm_password" required minlength="6">
                </div>
                <button type="submit" name="register" class="btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
        </div>
    </div>

    <div class="back-link">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>

    <script>
        function showTab(tabId) {
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            // Remove active class from all tab contents
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
            // Add active class to corresponding tab content
            document.getElementById(tabId + '-tab').classList.add('active');
        }

        function setLoading(form) {
            form.classList.add('loading');
        }

        // Password confirmation validation
        document.getElementById('reg_confirm_password').addEventListener('input', function() {
            const password = document.getElementById('reg_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('reg_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('reg_confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>