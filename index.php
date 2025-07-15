<?php
require_once 'config.php';

// Get current user data
$currentUser = getCurrentUser();

// Handle logout message
$logoutMessage = '';
if (isset($_SESSION['logout_message'])) {
    $logoutMessage = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apochetary Cartel - Your Trusted Online Pharmacy</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    <style>
        /* Login Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        .modal-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 1.5rem;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .tab-button.active {
            background: #007bff;
            color: white;
        }

        .tab-button:first-child {
            border-radius: 8px 0 0 8px;
        }

        .tab-button:last-child {
            border-radius: 0 8px 8px 0;
        }

        .form-group {
            margin-bottom: 1rem;
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
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }

        .modal-btn {
            width: 100%;
            padding: 0.75rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .modal-btn:hover {
            background: #0056b3;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: #28a745;
            color: white;
            border-radius: 8px;
            z-index: 1001;
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php if ($logoutMessage): ?>
        <div class="notification" id="logoutNotification">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($logoutMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h1><i class="fas fa-pills"></i> Apochetary Cartel</h1>
            </div>
            
            <div class="nav-menu">
                <a href="#home" class="nav-link active">Home</a>
                <div class="dropdown">
                    <a href="#products" class="nav-link">Products <i class="fas fa-chevron-down"></i></a>
                </div>
                <a href="#contact" class="nav-link">About</a>
                <a href="#contact" class="nav-link">Contact</a>
            </div>
            
            <div class="nav-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search products..." id="searchInput">
                    <button type="button" onclick="searchProducts()"><i class="fas fa-search"></i></button>
                </div>
                
                <a href="#cart" class="nav-link cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">3</span>
                </a>
                
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="#profile">Profile</a>
                            <a href="#orders">My Orders</a>
                            <a href="#prescriptions">My Prescriptions</a>
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <a href="#admin">Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="#" class="nav-link" onclick="openLoginModal()">
                        <i class="fas fa-user"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLoginModal()">&times;</span>
            <div class="modal-header">
                <h2><i class="fas fa-pills"></i> Apochetary Cartel</h2>
                <p>Access your account</p>
            </div>

            <div class="form-tabs">
                <button class="tab-button active" onclick="showModalTab('login')">Login</button>
                <button class="tab-button" onclick="showModalTab('register')">Register</button>
            </div>

            <!-- Login Form -->
            <div id="modal-login-tab" class="tab-content active">
                <form id="loginForm">
                    <div class="form-group">
                        <label for="modal_login_identifier">Username or Email</label>
                        <input type="text" id="modal_login_identifier" name="login_identifier" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_password">Password</label>
                        <input type="password" id="modal_password" name="password" required>
                    </div>
                    <button type="submit" class="modal-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="modal-register-tab" class="tab-content">
                <form id="registerForm">
                    <div class="form-group">
                        <label for="modal_reg_username">Username</label>
                        <input type="text" id="modal_reg_username" name="reg_username" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_reg_email">Email</label>
                        <input type="email" id="modal_reg_email" name="reg_email" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_reg_password">Password</label>
                        <input type="password" id="modal_reg_password" name="reg_password" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_reg_confirm_password">Confirm Password</label>
                        <input type="password" id="modal_reg_confirm_password" name="reg_confirm_password" required>
                    </div>
                    <button type="submit" class="modal-btn">
                        <i class="fas fa-user-plus"></i> Register
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="login.php" style="color: #007bff; text-decoration: none;">
                    Go to full login page
                </a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Your Health, Our Priority</h1>
                <p>Trusted online pharmacy delivering quality medications and healthcare products to your doorstep with professional care and expertise.</p>
                <div class="hero-buttons">
                    <a href="#products" class="btn btn-primary">Shop Now</a>
                    <a href="#about" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <i class="fas fa-prescription-bottle-alt"></i>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Why Choose Apochetary Cartel?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Safe & Secure</h3>
                    <p>All medications are sourced from licensed suppliers and verified by our certified pharmacists for your safety and peace of mind.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-truck"></i>
                    <h3>Fast Delivery</h3>
                    <p>Quick and reliable delivery to your doorstep with secure packaging and temperature-controlled shipping when needed.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-md"></i>
                    <h3>Expert Support</h3>
                    <p>Licensed pharmacists available 24/7 for consultation, medication guidance, and answering your health questions.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-file-prescription"></i>
                    <h3>Prescription Management</h3>
                    <p>Easy prescription upload, verification process, and automatic refill reminders to keep you on track.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products" id="products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-image">
                        <i class="fas fa-pills"></i>
                        <span class="prescription-badge">Rx</span>
                    </div>
                    <div class="product-info">
                        <h3>Amoxicillin 500mg</h3>
                        <p class="product-category">Antibiotics</p>
                        <div class="product-price">$24.99</div>
                        <div class="product-actions">
                            <a href="#product-details" class="btn btn-primary btn-sm">View Details</a>
                            <button onclick="addToCart(1)" class="btn btn-secondary btn-sm">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-image">
                        <i class="fas fa-tablets"></i>
                    </div>
                    <div class="product-info">
                        <h3>Ibuprofen 200mg</h3>
                        <p class="product-category">Pain Relief</p>
                        <div class="product-price">$8.99</div>
                        <div class="product-actions">
                            <a href="#product-details" class="btn btn-primary btn-sm">View Details</a>
                            <button onclick="addToCart(2)" class="btn btn-secondary btn-sm">
                                <i class="fas fa-cart-plus"></i>
                                                            </button>
                        </div>
                    </div>
                </div>
                <div class="product-card">
                    <div class="product-image">
                        <i class="fas fa-capsules"></i>
                        <span class="prescription-badge">Rx</span>
                    </div>
                    <div class="product-info">
                        <h3>Metformin 500mg</h3>
                        <p class="product-category">Diabetes Medication</p>
                        <div class="product-price">$49.99</div>
                        <div class="product-actions">
                            <a href="#product-details" class="btn btn-primary btn-sm">View Details</a>
                            <button onclick="addToCart(3)" class="btn btn-secondary btn-sm">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="product-card">
                    <div class="product-image">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <div class="product-info">
                        <h3>Insulin 100u/mL</h3>
                        <p class="product-category">Diabetes Treatment</p>
                        <div class="product-price">$89.99</div>
                        <div class="product-actions">
                            <a href="#product-details" class="btn btn-primary btn-sm">View Details</a>
                            <button onclick="addToCart(4)" class="btn btn-secondary btn-sm">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- About Us Section -->
    <section class="about" id="about">
        <div class="container">
            <h2>About Us</h2>
            <p>Welcome to Apochetary Cartel, your trusted online pharmacy committed to providing high-quality medications and healthcare products directly to your doorstep. Our team of licensed pharmacists ensures that all orders are filled accurately and promptly, with your safety and satisfaction as our top priority.</p>
            <p>We offer a wide range of prescription and over-the-counter medications, as well as health and wellness products, all at competitive prices. Our easy-to-use website and mobile app make shopping for your medications convenient and hassle-free.</p>
        </div>
    </section>
    <!-- Contact Us Section -->
    <section class="contact" id="contact">
        <div class="container">
            <h2>Contact Us</h2>
            <p>If you have any questions or need assistance, feel free to contact us using the form below or reach out via phone or email.</p>
            <form id="contactForm">
                <div class="form-group">
                    <label for="contact_name">Name</label>
                    <input type="text" id="contact_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="contact_email">Email</label>
                    <input type="email" id="contact_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="contact_message">Message</label>
                    <textarea id="contact_message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="modal-btn">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <h1><i class="fas fa-pills"></i> Apochetary Cartel</h1>
            </div>
            <div class="footer-links">
                <a href="#home">Home</a>
                <a href="#products">Products</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2023 Apochetary Cartel. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <!-- JavaScript for Modal and Other Interactions -->
    <script>
        // Function to open the login modal
        function openLoginModal() {
            document.getElementById('loginModal').style.display = 'block';
        }

        // Function to close the login modal
        function closeLoginModal() {
            document.getElementById('loginModal').style.display = 'none';
        }

        // Function to show the selected tab in the modal
        function showModalTab(tab) {
            const tabs = ['login', 'register'];
            tabs.forEach(t => {
                document.getElementById(`modal-${t}-tab`).classList.toggle('active', t === tab);
                document.querySelector(`.tab-button[data-tab="${t}"]`).classList.toggle('active', t === tab);
            });
        }

        // Function to handle search functionality
        function searchProducts() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            products.forEach(product => {
                const title = product.querySelector('.product-info h3').textContent.toLowerCase();
                const category = product.querySelector('.product-category').textContent.toLowerCase();
                if (title.includes(query) || category.includes(query)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        // Function to add a product to the cart (dummy implementation)
        function addToCart(productId) {
            alert(`Product ID ${productId} added to cart!`);
            // Here you can add actual logic to update the cart in the backend
        }

        // Automatically close the notification after 5 seconds
        window.onload = function() {
            const notification = document.getElementById('logoutNotification');
            if (notification) {
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 5000);
            }
        };
    </script>
</body>
</html>