<?php
require_once 'config.php';

// Get categories for filter
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = ["p.is_active = 1"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.active_ingredient LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_id) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Sort options
$sort_options = [
    'name' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'price' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest' => 'p.created_at DESC'
];

$order_by = isset($sort_options[$sort]) ? $sort_options[$sort] : 'p.name ASC';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products p $where_clause";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetch()['total'];
$total_pages = ceil($total_products / $per_page);

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $where_clause 
        ORDER BY $order_by 
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get current category name
$current_category = '';
if ($category_id) {
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    $current_category = $category ? $category['name'] : '';
}

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
    <title>Products - Apochetary Cartel</title>
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
                <a href="index.php" class="nav-link">Home</a>
                <div class="dropdown">
                    <a href="products.php" class="nav-link active">Products <i class="fas fa-chevron-down"></i></a>
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
                    <input type="text" placeholder="Search products..." id="searchInput" value="<?php echo $search; ?>">
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Products</h1>
            <p>
                <?php if ($current_category): ?>
                    <?php echo $current_category; ?> - 
                <?php endif; ?>
                <?php echo $total_products; ?> products found
                <?php if ($search): ?>
                    for "<?php echo $search; ?>"
                <?php endif; ?>
            </p>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="products-layout">
                <!-- Filters Sidebar -->
                <div class="filters-sidebar">
                    <h3>Filters</h3>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <h4>Categories</h4>
                        <ul class="filter-list">
                            <li><a href="products.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
                                   class="<?php echo !$category_id ? 'active' : ''; ?>">All Products</a></li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="products.php?category=<?php echo $category['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="<?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                                        <?php echo $category['name']; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Prescription Filter -->
                    <div class="filter-group">
                        <h4>Prescription</h4>
                        <ul class="filter-list">
                            <li><a href="#">Over-the-Counter</a></li>
                            <li><a href="#">Prescription Required</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="products-content">
                    <!-- Sort and View Options -->
                    <div class="products-toolbar">
                        <div class="sort-options">
                            <label for="sort">Sort by:</label>
                            <select id="sort" onchange="sortProducts(this.value)">
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <div class="view-options">
                            <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_products); ?> of <?php echo $total_products; ?> products</span>
                        </div>
                    </div>
                    
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <i class="fas fa-search"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your search or filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="<?php echo $product['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                                             alt="<?php echo sanitize($product['name']); ?>">
                                        <?php if ($product['requires_prescription']): ?>
                                            <span class="prescription-badge">Rx</span>
                                        <?php endif; ?>
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="out-of-stock-badge">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3><?php echo sanitize($product['name']); ?></h3>
                                        <p class="product-category"><?php echo sanitize($product['category_name']); ?></p>
                                        <?php if ($product['active_ingredient']): ?>
                                            <p class="product-ingredient"><?php echo sanitize($product['active_ingredient']); ?></p>
                                        <?php endif; ?>
                                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                        <div class="product-actions">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                            <?php if (Session::isLoggedIn() && $product['stock_quantity'] > 0): ?>
                                                <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn btn-secondary btn-sm">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" 
                                       class="pagination-btn">Previous</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" 
                                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_id ? '&category=' . $category_id : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" 
                                       class="pagination-btn">Next</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
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
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +1-800-PHARMACY</p>
                    <p><i class="fas fa-envelope"></i> info@apochetary.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Apochetary Cartel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function searchProducts() {
            const searchInput = document.getElementById('searchInput');
            const searchValue = searchInput.value.trim();
            
            if (searchValue) {
                window.location.href = `products.php?search=${encodeURIComponent(searchValue)}`;
            } else {
                window.location.href = 'products.php';
            }
        }
        
        function sortProducts(sortValue) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', sortValue);
            window.location.href = `products.php?${urlParams.toString()}`;
        }
        
        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
        
        function addToCart(productId) {
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount) {
                        cartCount.textContent = data.cart_count;
                    }
                    
                    // Show success message
                    showNotification('Product added to cart!', 'success');
                } else {
                    showNotification(data.message || 'Failed to add product to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>