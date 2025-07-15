<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Apochetary Cartel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 30px;
            border-bottom: 1px solid #34495e;
        }

        .sidebar-header h2 {
            color: #3498db;
            margin-bottom: 5px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-nav li {
            margin-bottom: 5px;
        }

        .sidebar-nav a {
            display: block;
            color: #bdc3c7;
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #34495e;
            color: #3498db;
        }

        .sidebar-nav i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2c3e50;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #3498db;
        }

        .stat-card h3 {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 1.1em;
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            background: #34495e;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h2 {
            margin: 0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.active {
            background: #d4edda;
            color: #155724;
        }

        .status.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.completed {
            background: #d4edda;
            color: #155724;
        }

        .status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Forms */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading spinner */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .hidden {
            display: none !important;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-pills"></i> Admin Panel</h2>
                <p>Apochetary Cartel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#products" class="nav-link"><i class="fas fa-pills"></i> Products</a></li>
                <li><a href="#orders" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="#users" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="#categories" class="nav-link"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="#inventory" class="nav-link"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="#reports" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#settings" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                    <a href="#logout" class="btn btn-danger btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div id="dashboard-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-pills"></i>
                        <h3 id="total-products">0</h3>
                        <p>Total Products</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-shopping-cart"></i>
                        <h3 id="total-orders">0</h3>
                        <p>Total Orders</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3 id="total-users">0</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-dollar-sign"></i>
                        <h3 id="total-revenue">$0</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-header">
                        <h2>Recent Orders</h2>
                        <a href="#orders" class="btn btn-primary">View All Orders</a>
                    </div>
                    <table id="recent-orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Recent orders will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Products Section -->
            <div id="products-section" class="hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Products Management</h2>
                        <button class="btn btn-primary" onclick="openProductModal()">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>
                    <div style="padding: 20px;">
                        <div class="search-bar">
                            <input type="text" id="product-search" placeholder="Search products..." onkeyup="searchProducts()">
                        </div>
                    </div>
                    <table id="products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Products will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Orders Section -->
            <div id="orders-section" class="hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Orders Management</h2>
                        <select id="order-status-filter" onchange="filterOrders()">
                            <option value="">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <table id="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Orders will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users-section" class="hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Users Management</h2>
                        <button class="btn btn-primary" onclick="openUserModal()">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                    <table id="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Categories Section -->
            <div id="categories-section" class="hidden">
                <div class="table-container">
                    <div class="table-header">
                        <h2>Categories Management</h2>
                        <button class="btn btn-primary" onclick="openCategoryModal()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                    <table id="categories-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Products Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Categories will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Loading Spinner -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        </main>
    </div>

    <!-- Product Modal -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <h2 id="product-modal-title">Add Product</h2>
            <form id="product-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="product-name">Product Name</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" name="category_id" required>
                            <!-- Categories will be loaded here -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Price</label>
                        <input type="number" id="product-price" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock Quantity</label>
                        <input type="number" id="product-stock" name="stock_quantity" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="product-prescription" name="requires_prescription">
                        Requires Prescription
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Product</button>
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let currentSection = 'dashboard';
        const API_BASE = 'api/';
        const authToken = localStorage.getItem('admin_token');

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            if (!authToken) {
                window.location.href = 'admin-login.html';
                return;
            }
            
            initializeDashboard();
            loadDashboardData();
            
            // Add event listeners for navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const section = this.getAttribute('href').substring(1);
                    showSection(section);
                });
            });
        });

        // Navigation functions
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('[id$="-section"]').forEach(el => {
                el.classList.add('hidden');
            });
            
            // Show selected section
            document.getElementById(section + '-section').classList.remove('hidden');
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`[href="#${section}"]`).classList.add('active');
            
            currentSection = section;
            
            // Load section data
            loadSectionData(section);
        }

        function loadSectionData(section) {
            switch(section) {
                case 'dashboard':
                    loadDashboardData();
                    break;
                case 'products':
                    loadProducts();
                    break;
                case 'orders':
                    loadOrders();
                    break;
                case 'users':
                    loadUsers();
                    break;
                case 'categories':
                    loadCategories();
                    break;
            }
        }

        // Dashboard functions
        function initializeDashboard() {
            // Set up any initial dashboard configurations
        }

        async function loadDashboardData() {
            showLoading();
            try {
                // Load dashboard stats
                await Promise.all([
                    loadDashboardStats(),
                    loadRecentOrders()
                ]);
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
            hideLoading();
        }

        async function loadDashboardStats() {
            // Mock data - replace with actual API calls
            const stats = {
                totalProducts: 156,
                totalOrders: 89,
                totalUsers: 245,
                totalRevenue: 15640.50
            };
            
            document.getElementById('total-products').textContent = stats.totalProducts;
            document.getElementById('total-orders').textContent = stats.totalOrders;
            document.getElementById('total-users').textContent = stats.totalUsers;
            document.getElementById('total-revenue').textContent = `$${stats.totalRevenue.toFixed(2)}`;
        }

        async function loadRecentOrders() {
            // Mock data - replace with actual API call
            const orders = [
                { id: 1001, customer: 'John Doe', total: 45.99, status: 'completed', date: '2024-01-15' },
                { id: 1002, customer: 'Jane Smith', total: 78.50, status: 'processing', date: '2024-01-15' },
                { id: 1003, customer: 'Mike Johnson', total: 23.99, status: 'pending', date: '2024-01-14' }
            ];
            
            const tbody = document.querySelector('#recent-orders-table tbody');
            tbody.innerHTML = orders.map(order => `
                <tr>
                    <td>#${order.id}</td>
                    <td>${order.customer}</td>
                    <td>$${order.total}</td>
                    <td><span class="status ${order.status}">${order.status}</span></td>
                    <td>${order.date}</td>
                </tr>
            `).join('');
        }

        // Products functions
        async function loadProducts() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const products = [
                    { id: 1, name: 'Amoxicillin 500mg', category: 'Antibiotics', price: 24.99, stock: 100, status: 'active' },
                    { id: 2, name: 'Ibuprofen 200mg', category: 'Pain Relief', price: 8.99, stock: 250, status: 'active' },
                    { id: 3, name: 'Vitamin D3 1000 IU', category: 'Vitamins', price: 15.99, stock: 150, status: 'active' }
                ];
                
                const tbody = document.querySelector('#products-table tbody');
                tbody.innerHTML = products.map(product => `
                    <tr>
                        <td>${product.id}</td>
                        <td>${product.name}</td>
                        <td>${product.category}</td>
                        <td>$${product.price}</td>
                        <td>${product.stock}</td>
                        <td><span class="status ${product.status}">${product.status}</span></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editProduct(${product.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteProduct(${product.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading products:', error);
            }
            hideLoading();
        }

        function searchProducts() {
            const searchTerm = document.getElementById('product-search').value.toLowerCase();
            const rows = document.querySelectorAll('#products-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        // Orders functions
        async function loadOrders() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const orders = [
                    { id: 1001, customer: 'John Doe', items: 3, total: 45.99, status: 'completed', date: '2024-01-15' },
                    { id: 1002, customer: 'Jane Smith', items: 2, total: 78.50, status: 'processing', date: '2024-01-15' },
                    { id: 1003, customer: 'Mike Johnson', items: 1, total: 23.99, status: 'pending', date: '2024-01-14' }
                ];
                
                const tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = orders.map(order => `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${order.customer}</td>
                        <td>${order.items}</td>
                        <td>$${order.total}</td>
                        <td><span class="status ${order.status}">${order.status}</span></td>
                        <td>${order.date}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewOrder(${order.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editOrder(${order.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading orders:', error);
            }
            hideLoading();
        }

        function filterOrders() {
            const filter = document.getElementById('order-status-filter').value;
            const rows = document.querySelectorAll('#orders-table tbody tr');
            
            rows.forEach(row => {
                const status = row.querySelector('.status').textContent;
                row.style.display = !filter || status === filter ? '' : 'none';
            });
        }

        // Users functions
        async function loadUsers() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const users = [
                    { id: 1, name: 'John Doe', email: 'john@example.com', phone: '555-0123', status: 'active', joined: '2024-01-10' },
                    { id: 2, name: 'Jane Smith', email: 'jane@example.com', phone: '555-0124', status: 'active', joined: '2024-01-12' },
                    { id: 3, name: 'Mike Johnson', email: 'mike@example.com', phone: '555-0125', status: 'inactive', joined: '2024-01-14' }
                ];
                
                const tbody = document.querySelector('#users-table tbody');
                tbody.innerHTML = users.map(user => `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.phone}</td>
                        <td><span class="status ${user.status}">${user.status}</span></td>
                        <td>${user.joined}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewUser(${user.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editUser(${user.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading users:', error);
            }
            hideLoading();
        }

        // Categories functions
        async function loadCategories() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const categories = [
                    { id: 1, name: 'Antibiotics', products: 15, status: 'active' },
                    { id: 2, name: 'Pain Relief', products: 22, status: 'active' },
                    { id: 3, name: 'Vitamins & Supplements', products: 35, status: 'active' },
                    { id: 4, name: 'Personal Care', products: 18, status: 'active' }
                ];
                
                const tbody = document.querySelector('#categories-table tbody');
                tbody.innerHTML = categories.map(category => `
                    <tr>
                        <td>${category.id}</td>
                        <td>${category.name}</td>
                        <td>${category.products}</td>
                        <td><span class="status ${category.status}">${category.status}</span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewCategory(${category.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editCategory(${category.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading categories:', error);
            }
            hideLoading();
        }

        // Orders functions
        async function loadOrders() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const orders = [
                    { id: 1001, customer: 'John Doe', items: 3, total: 45.99, status: 'completed', date: '2024-01-15' },
                    { id: 1002, customer: 'Jane Smith', items: 2, total: 78.50, status: 'processing', date: '2024-01-15' },
                    { id: 1003, customer: 'Mike Johnson', items: 1, total: 23.99, status: 'pending', date: '2024-01-14' }
                ];
                
                const tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = orders.map(order => `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${order.customer}</td>
                        <td>${order.items}</td>
                        <td>$${order.total}</td>
                        <td><span class="status ${order.status}">${order.status}</span></td>
                        <td>${order.date}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewOrder(${order.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editOrder(${order.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading orders:', error);
            }
            hideLoading();
        }

        // Products functions
        async function loadProducts() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const products = [
                    { id: 1, name: 'Product 1', price: 19.99, status: 'active' },
                    { id: 2, name: 'Product 2', price: 29.99, status: 'active' },
                    { id: 3, name: 'Product 3', price: 39.99, status: 'active' },
                    { id: 4, name: 'Product 4', price: 49.99, status: 'active' }
                ];
                
                const tbody = document.querySelector('#products-table tbody');
                tbody.innerHTML = products.map(product => `
                    <tr>
                        <td>${product.id}</td>
                        <td>${product.name}</td>
                        <td>$${product.price}</td>
                        <td><span class="status ${product.status}">${product.status}</span></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewProduct(${product.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editProduct(${product.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading products:', error);
            }
            hideLoading();
        }

        // Users functions
        async function loadUsers() {
            showLoading();
            try {
                // Mock data - replace with actual API call
                const users = [
                    { id: 1, name: 'John Doe', email: '1dYK7@example.com', phone: '123-456-7890', status: 'active', joined: '2023-01-01' },
                    { id: 2, name: 'Jane Smith', email: 'E1a0S@example.com', phone: '987-654-3210', status: 'inactive', joined: '2023-02-01' },
                    { id: 3, name: 'Mike Johnson', email: '8Tt6t@example.com', phone: '555-555-5555', status: 'active', joined: '2023-03-01' }
                ];
                
                const tbody = document.querySelector('#users-table tbody');
                tbody.innerHTML = users.map(user => `
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.name}</td>
                        <td>${user.email}</td>
                        <td>${user.phone}</td>
                        <td><span class="status ${user.status}">${user.status}</span></td>
                        <td>${user.joined}</td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="viewUser(${user.id})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editUser(${user.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error loading users:', error);
            }
            hideLoading();
        }

        // Dashboard functions
        async function loadDashboardData() {
            showLoading();
            try {
                // Load dashboard stats
                await Promise.all([
                    loadDashboardStats(),
                    loadRecentOrders()
                ]);
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
            hideLoading();
        }

        // Load initial data
        loadDashboardData();
        loadUsers();
        loadProducts();
        loadOrders();
        loadCategories();
    </script>
</body>
</html>

