<?php
require_once 'config.php';

// Get categories for navigation
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get featured products
$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.is_active = 1 
                      ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get cart count for logged-in users
$cart_count = 0;
if (Session::isLoggedIn()) {
    $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([Session::getUserId()]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apochetary Cartel - Your Trusted Online Pharmacy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h1><i class="fas fa-pills"></i> Apochetary Cartel</h1>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link active">Home</a>
                <div class="dropdown">
                    <a href="products.php" class="nav-link">Products <i class="fas fa-chevron-down"></i></a>
                    <div class="dropdown-content">
                        <?php foreach ($categories as $category): ?>
                            <a href="products.php?category=<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </div>
            
            <div class="nav-actions">
                <div class="search-box">
                    <input type="text" placeholder="Search products..." id="searchInput">
                    <button type="button" onclick="searchProducts()"><i class="fas fa-search"></i></button>
                </div>
                
                <?php if (Session::isLoggedIn()): ?>
                    <a href="cart.php" class="nav-link cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo $cart_count; ?></span>
                    </a>
                    <div class="dropdown">
                        <a href="#" class="nav-link">
                            <i class="fas fa-user"></i> 
                            <?php echo sanitize(Session::get('username')); ?>
                        </a>
                        <div class="dropdown-content">
                            <a href="profile.php">Profile</a>
                            <a href="orders.php">My Orders</a>
                            <?php if (Session::getRole() === 'admin'): ?>
                                <a href="admin/dashboard.php">Admin Dashboard</a>
                            <?php elseif (Session::getRole() === 'pharmacist'): ?>
                                <a href="pharmacist/dashboard.php">Pharmacist Dashboard</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Your Health, Our Priority</h1>
            <p>Trusted online pharmacy delivering quality medications and healthcare products to your doorstep</p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary">Shop Now</a>
                <a href="about.php" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
        <div class="hero-image">
            <i class="fas fa-prescription-bottle-alt"></i>
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
                    <p>All medications are sourced from licensed suppliers and verified by our pharmacists</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-truck"></i>
                    <h3>Fast Delivery</h3>
                    <p>Quick and reliable delivery to your doorstep with secure packaging</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-md"></i>
                    <h3>Expert Support</h3>
                    <p>Licensed pharmacists available for consultation and medication guidance</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-file-prescription"></i>
                    <h3>Prescription Management</h3>
                    <p>Easy prescription upload and verification process</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $product['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                                 alt="<?php echo sanitize($product['name']); ?>">
                            <?php if ($product['requires_prescription']): ?>
                                <span class="prescription-badge">Rx</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?php echo sanitize($product['name']); ?></h3>
                            <p class="product-category"><?php echo sanitize($product['category_name']); ?></p>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                <?php if (Session::isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Apochetary Cartel</h3>
                    <p>Your trusted online pharmacy, committed to providing safe, effective medications and healthcare products.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Categories</h4>
                    <ul>
                        <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                            <li><a href="products.php?category=<?php echo $category['id']; ?>"><?php echo $category['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +1-800-PHARMACY</p>
                    <p><i class="fas fa-envelope"></i> info@apochetary.com</p>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Health Street, Medical City</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Apochetary Cartel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>