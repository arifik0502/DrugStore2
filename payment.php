<?php
require_once 'config.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    redirect('login.php');
}

// Get pending order from session
$pending_order = Session::get('pending_order');
if (!$pending_order) {
    redirect('cart.php');
}

$user_id = Session::getUserId();

// Generate order ID
$order_id = 'ORD' . date('YmdHis') . str_pad($user_id, 4, '0', STR_PAD_LEFT);

// Generate QR code data
$qr_data = [
    'order_id' => $order_id,
    'merchant_name' => 'Apochetary Cartel',
    'merchant_id' => 'APOC001',
    'amount' => $pending_order['total'],
    'currency' => 'USD',
    'description' => 'Pharmacy Order Payment',
    'timestamp' => date('Y-m-d H:i:s')
];

$qr_string = json_encode($qr_data);
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qr_string);

// Store payment session
Session::set('payment_session', [
    'order_id' => $order_id,
    'amount' => $pending_order['total'],
    'created_at' => time(),
    'status' => 'pending'
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Apochetary Cartel</title>
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
                <a href="products.php" class="nav-link">Products</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
            </div>
            
            <div class="nav-actions">
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
            </div>
        </div>
    </nav>

    <!-- Payment Section -->
    <section class="payment-section">
        <div class="container">
            <div class="payment-header">
                <h1>Payment</h1>
                <div class="checkout-steps">
                    <div class="step active">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Cart</span>
                    </div>
                    <div class="step active">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Shipping</span>
                    </div>
                    <div class="step current">
                        <i class="fas fa-credit-card"></i>
                        <span>Payment</span>
                    </div>
                    <div class="step">
                        <i class="fas fa-check-circle"></i>
                        <span>Confirmation</span>
                    </div>
                </div>
            </div>

            <div class="payment-layout">
                <div class="payment-main">
                    <div class="payment-card">
                        <h2><i class="fas fa-qrcode"></i> QR Code Payment</h2>
                        
                        <div class="qr-payment-container">
                            <div class="qr-code-section">
                                <div class="qr-code-wrapper">
                                    <img src="<?php echo $qr_code_url; ?>" alt="QR Code" class="qr-code">
                                    <div class="qr-overlay" id="qrOverlay">
                                        <div class="scanning-animation">
                                            <div class="scan-line"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="qr-instructions">
                                    <h3>Scan to Pay</h3>
                                    <p>Use your mobile banking app or payment app to scan the QR code above</p>
                                    <div class="payment-apps">
                                        <i class="fab fa-apple-pay"></i>
                                        <i class="fab fa-google-pay"></i>
                                        <i class="fab fa-paypal"></i>
                                        <i class="fas fa-university"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-details">
                                <h3>Payment Details</h3>
                                <div class="detail-row">
                                    <span>Order ID:</span>
                                    <span class="order-id"><?php echo $order_id; ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Merchant:</span>
                                    <span>Apochetary Cartel</span>
                                </div>
                                <div class="detail-row">
                                    <span>Amount:</span>
                                    <span class="amount"><?php echo formatPrice($pending_order['total']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Currency:</span>
                                    <span>USD</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-status" id="paymentStatus">
                            <div class="status-indicator pending">
                                <i class="fas fa-clock"></i>
                                <span>Waiting for payment...</span>
                            </div>
                            <div class="countdown-timer">
                                <span>Session expires in: </span>
                                <span id="countdown">15:00</span>
                            </div>
                        </div>
                        
                        <div class="payment-actions">
                            <button onclick="simulatePayment()" class="btn btn-success">
                                <i class="fas fa-mobile-alt"></i> Simulate Payment
                            </button>
                            <button onclick="refreshQR()" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> Refresh QR Code
                            </button>
                            <a href="checkout.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Back to Checkout
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="payment-sidebar">
                    <div class="order-summary">
                        <h2>Order Summary</h2>
                        
                        <div class="order-items">
                            <?php foreach ($pending_order['cart_items'] as $item): ?>
                                <div class="order-item">
                                    <img src="<?php echo $item['image_url'] ?: 'assets/images/default-product.jpg'; ?>" 
                                         alt="<?php echo sanitize($item['name']); ?>">
                                    <div class="item-details">
                                        <h4><?php echo sanitize($item['name']); ?></h4>
                                        <p>Qty: <?php echo $item['quantity']; ?></p>
                                        <?php if ($item['requires_prescription']): ?>
                                            <span class="prescription-badge">Rx Required</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-price">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-totals">
                            <div class="total-row">
                                <span>Subtotal:</span>
                                <span><?php echo formatPrice($pending_order['subtotal']); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Tax:</span>
                                <span><?php echo formatPrice($pending_order['tax_amount']); ?></span>
                            </div>
                            <div class="total-row">
                                <span>Shipping:</span>
                                <span><?php echo $pending_order['shipping_fee'] > 0 ? formatPrice($pending_order['shipping_fee']) : 'FREE'; ?></span>
                            </div>
                            <div class="total-row final-total">
                                <span>Total:</span>
                                <span><?php echo formatPrice($pending_order['total']); ?></span>
                            </div>
                        </div>

                        <div class="shipping-info">
                            <h3>Shipping Address</h3>
                            <p><?php echo sanitize($pending_order['shipping_address']['full_name']); ?></p>
                            <p><?php echo sanitize($pending_order['shipping_address']['address_line1']); ?></p>
                            <?php if ($pending_order['shipping_address']['address_line2']): ?>
                                <p><?php echo sanitize($pending_order['shipping_address']['address_line2']); ?></p>
                            <?php endif; ?>
                            <p><?php echo sanitize($pending_order['shipping_address']['city']); ?>, 
                               <?php echo sanitize($pending_order['shipping_address']['state']); ?> 
                               <?php echo sanitize($pending_order['shipping_address']['zip_code']); ?></p>
                        </div>
                    </div>
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
                    <p>Your trusted online pharmacy.</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Apochetary Cartel. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        .payment-section {
            padding: 2rem 0;
            background: #f8f9fa;
            min-height: 80vh;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .checkout-steps {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            opacity: 0.5;
            transition: opacity 0.3s;
        }

        .step.active, .step.current {
            opacity: 1;
            color: #007bff;
        }

        .payment-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .payment-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .qr-payment-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .qr-code-section {
            text-align: center;
        }

        .qr-code-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .qr-code {
            width: 250px;
            height: 250px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: transform 0.3s;
        }

        .qr-code:hover {
            transform: scale(1.02);
        }

        .qr-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 123, 255, 0.1);
            border-radius: 8px;
            display: none;
        }

        .qr-overlay.scanning {
            display: block;
        }

        .scanning-animation {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .scan-line {
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #007bff;
            animation: scan 2s infinite;
        }

        @keyframes scan {
            0% { top: 0; }
            50% { top: 50%; }
            100% { top: 100%; }
        }

        .qr-instructions h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .payment-apps {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-apps i {
            font-size: 2rem;
            color: #666;
            transition: color 0.3s;
        }

        .payment-apps i:hover {
            color: #007bff;
        }

        .payment-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .payment-details h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .order-id {
            font-family: monospace;
            font-weight: bold;
            color: #007bff;
        }

        .amount {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }

        .payment-status {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .status-indicator.pending {
            color: #ffc107;
        }

        .status-indicator.processing {
            color: #007bff;
        }

        .status-indicator.success {
            color: #28a745;
        }

        .status-indicator.failed {
            color: #dc3545;
        }

        .countdown-timer {
            font-size: 1.1rem;
            font-weight: bold;
        }

        #countdown {
            color: #dc3545;
            font-family: monospace;
        }

        .payment-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-align: center;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
        }

        .btn-outline:hover {
            background: #007bff;
            color: white;
        }

        .order-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .item-details {
            flex: 1;
        }

        .item-details h4 {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .item-details p {
            margin: 0;
            color: #666;
            font-size: 0.8rem;
        }

        .prescription-badge {
            background: #dc3545;
            color: white;
            padding: 0.125rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .item-price {
            font-weight: bold;
            color: #007bff;
        }

        .order-totals {
            border-top: 2px solid #e9ecef;
            padding-top: 1rem;
            margin-bottom: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .final-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #007bff;
            border-top: 1px solid #e9ecef;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .shipping-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }

        .shipping-info h3 {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .shipping-info p {
            margin: 0.25rem 0;
            color: #666;
        }

        /* Success/Error Messages */
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .payment-layout {
                grid-template-columns: 1fr;
            }
            
            .qr-payment-container {
                grid-template-columns: 1fr;
            }
            
            .payment-actions {
                flex-direction: column;
            }
            
            .checkout-steps {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
    </style>

    <script>
        // Countdown timer
        let countdownTime = 15 * 60; // 15 minutes in seconds
        let countdownInterval;

        function startCountdown() {
            countdownInterval = setInterval(() => {
                const minutes = Math.floor(countdownTime / 60);
                const seconds = countdownTime % 60;
                
                document.getElementById('countdown').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                if (countdownTime <= 0) {
                    clearInterval(countdownInterval);
                    sessionExpired();
                }
                
                countdownTime--;
            }, 1000);
        }

        function sessionExpired() {
            const statusIndicator = document.querySelector('.status-indicator');
            statusIndicator.innerHTML = '<i class="fas fa-times-circle"></i><span>Session expired</span>';
            statusIndicator.className = 'status-indicator failed';
            
            // Disable buttons
            document.querySelectorAll('.payment-actions button').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
            });
            
            showAlert('Session expired! Please start a new payment session.', 'error');
        }

        function showAlert(message, type = 'success') {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            `;
            
            // Insert alert at the top of payment card
            const paymentCard = document.querySelector('.payment-card');
            paymentCard.insertBefore(alert, paymentCard.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        function simulatePayment() {
            const statusIndicator = document.querySelector('.status-indicator');
            const qrOverlay = document.getElementById('qrOverlay');
            
            // Show processing state
            statusIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing payment...</span>';
            statusIndicator.className = 'status-indicator processing';
            
            // Show scanning animation
            qrOverlay.classList.add('scanning');
            
            // Simulate payment processing
            setTimeout(() => {
                // Remove scanning animation
                qrOverlay.classList.remove('scanning');
                
                // Show success state
                statusIndicator.innerHTML = '<i class="fas fa-check-circle"></i><span>Payment successful!</span>';
                statusIndicator.className = 'status-indicator success';
                
                // Stop countdown
                clearInterval(countdownInterval);
                document.getElementById('countdown').textContent = 'Complete';
                
                // Show success message
                showAlert('Payment completed successfully! Your order is being processed.', 'success');
                
                // Update checkout steps
                const confirmationStep = document.querySelector('.checkout-steps .step:last-child');
                confirmationStep.classList.add('current');
                
                // Disable payment buttons and show redirect option
                setTimeout(() => {
                    const paymentActions = document.querySelector('.payment-actions');
                    paymentActions.innerHTML = `
                        <a href="order-confirmation.php" class="btn btn-success">
                            <i class="fas fa-receipt"></i> View Order Confirmation
                        </a>
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    `;
                }, 2000);
                
            }, 3000);
        }

        function refreshQR() {
            const qrCode = document.querySelector('.qr-code');
            const refreshBtn = document.querySelector('button[onclick="refreshQR()"]');
            
            // Show loading state
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
            
            // Simulate QR refresh
            setTimeout(() => {
                // Reset countdown
                countdownTime = 15 * 60;
                clearInterval(countdownInterval);
                startCountdown();
                
                // Reset status
                const statusIndicator = document.querySelector('.status-indicator');
                statusIndicator.innerHTML = '<i class="fas fa-clock"></i><span>Waiting for payment...</span>';
                statusIndicator.className = 'status-indicator pending';
                
                // Restore button
                refreshBtn.innerHTML = '<i class="fas fa-sync"></i> Refresh QR Code';
                refreshBtn.disabled = false;
                
                // Add timestamp to QR code URL to force refresh
                const currentUrl = qrCode.src;
                const separator = currentUrl.includes('?') ? '&' : '?';
                qrCode.src = currentUrl + separator + 't=' + Date.now();
                
                showAlert('QR code refreshed successfully!', 'success');
            }, 1000);
        }

        // Initialize countdown when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
        });

        // Simulate real-time payment status checking (optional)
        function checkPaymentStatus() {
            // This would normally make an AJAX call to check payment status
            // For demo purposes, we'll just return a random status
            const statuses = ['pending', 'processing', 'success', 'failed'];
            return statuses[Math.floor(Math.random() * statuses.length)];
        }
    </script>
</body>
</html>