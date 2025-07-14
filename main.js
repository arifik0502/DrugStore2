// Mock data for products
const products = [
    { id: 1, name: "Amoxicillin 500mg", category: "Antibiotics", price: 24.99, prescription: true },
    { id: 2, name: "Ibuprofen 200mg", category: "Pain Relief", price: 8.99, prescription: false },
    { id: 3, name: "Vitamin D3 1000 IU", category: "Vitamins & Supplements", price: 15.99, prescription: false },
    { id: 4, name: "Metformin 500mg", category: "Diabetes Care", price: 18.99, prescription: true },
    { id: 5, name: "Hand Sanitizer 500ml", category: "Personal Care", price: 6.99, prescription: false },
    { id: 6, name: "Digital Thermometer", category: "Medical Devices", price: 12.99, prescription: false },
    { id: 7, name: "Adhesive Bandages", category: "First Aid", price: 4.99, prescription: false },
    { id: 8, name: "Eye Drops", category: "Eye Care", price: 9.99, prescription: false }
];

// Cart management
let cart = [];
let cartCount = 0;

// User authentication mock
const mockUser = {
    id: 1,
    name: "John Doe",
    email: "john.doe@email.com",
    isLoggedIn: true,
    prescriptions: [
        { id: 1, medication: "Amoxicillin 500mg", refillsLeft: 2 },
        { id: 4, medication: "Metformin 500mg", refillsLeft: 1 }
    ]
};

// Search functionality
function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    
    if (!searchTerm) {
        showMessage('Please enter a search term', 'error');
        return;
    }

    // Simulate API call
    setTimeout(() => {
        const results = products.filter(product => 
            product.name.toLowerCase().includes(searchTerm) ||
            product.category.toLowerCase().includes(searchTerm)
        );

        if (results.length > 0) {
            showMessage(`Found ${results.length} product(s) matching "${searchTerm}"`, 'success');
            console.log('Search results:', results);
            // In a real app, you'd update the UI with results
        } else {
            showMessage(`No products found matching "${searchTerm}"`, 'error');
        }
    }, 500);
}

// Add to cart functionality
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    
    if (!product) {
        showMessage('Product not found', 'error');
        return;
    }

    // Check if prescription is required and user has valid prescription
    if (product.prescription && !hasValidPrescription(productId)) {
        showMessage('Valid prescription required for this medication', 'error');
        return;
    }

    // Check if product already in cart
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: product.name,
            price: product.price,
            quantity: 1
        });
    }

    updateCartCount();
    showMessage(`${product.name} added to cart successfully!`, 'success');
    
    // Simulate API call to backend
    setTimeout(() => {
        console.log('Cart updated on server:', cart);
    }, 300);
}

// Check if user has valid prescription
function hasValidPrescription(productId) {
    if (!mockUser.isLoggedIn) return false;
    
    const prescription = mockUser.prescriptions.find(p => p.id === productId);
    return prescription && prescription.refillsLeft > 0;
}

// Update cart count in UI
function updateCartCount() {
    cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
    }
}

// Show messages to user
function showMessage(text, type = 'success') {
    const message = document.createElement('div');
    message.className = `${type}-message`;
    message.textContent = text;
    message.style.position = 'fixed';
    message.style.top = '20px';
    message.style.right = '20px';
    message.style.zIndex = '9999';
    message.style.minWidth = '300px';
    message.style.animation = 'fadeInUp 0.3s ease';
    
    document.body.appendChild(message);
    
    setTimeout(() => {
        message.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            if (message.parentNode) {
                message.remove();
            }
        }, 300);
    }, 3000);
}

// User authentication functions
function login(email, password) {
    // Simulate API call
    return new Promise((resolve) => {
        setTimeout(() => {
            if (email === "john.doe@email.com" && password === "password123") {
                mockUser.isLoggedIn = true;
                showMessage('Login successful!', 'success');
                resolve({ success: true, user: mockUser });
            } else {
                showMessage('Invalid credentials', 'error');
                resolve({ success: false });
            }
        }, 1000);
    });
}

function logout() {
    mockUser.isLoggedIn = false;
    cart = [];
    cartCount = 0;
    updateCartCount();
    showMessage('Logged out successfully', 'success');
    
    // Redirect to home or login page
    window.location.href = '#home';
}

// Prescription management
function uploadPrescription(file) {
    if (!file || !file.type.includes('image')) {
        showMessage('Please upload a valid image file', 'error');
        return;
    }

    // Simulate file upload
    const formData = new FormData();
    formData.append('prescription', file);
    
    // Mock API call
    setTimeout(() => {
        showMessage('Prescription uploaded successfully! Our pharmacist will review it shortly.', 'success');
        console.log('Prescription uploaded:', file.name);
    }, 2000);
}

// Handle prescription refills
function requestRefill(prescriptionId) {
    const prescription = mockUser.prescriptions.find(p => p.id === prescriptionId);
    
    if (!prescription) {
        showMessage('Prescription not found', 'error');
        return;
    }

    if (prescription.refillsLeft <= 0) {
        showMessage('No refills remaining. Please contact your doctor.', 'error');
        return;
    }

    // Simulate API call
    setTimeout(() => {
        prescription.refillsLeft -= 1;
        showMessage(`Refill requested for ${prescription.medication}`, 'success');
        console.log('Refill requested:', prescription);
    }, 1000);
}

// Cart operations
function removeFromCart(productId) {
    const itemIndex = cart.findIndex(item => item.id === productId);
    
    if (itemIndex !== -1) {
        const item = cart[itemIndex];
        cart.splice(itemIndex, 1);
        updateCartCount();
        showMessage(`${item.name} removed from cart`, 'success');
    }
}

function updateCartItemQuantity(productId, quantity) {
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        if (quantity <= 0) {
            removeFromCart(productId);
        } else {
            item.quantity = quantity;
            updateCartCount();
        }
    }
}

function getCartTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// Checkout process
function checkout() {
    if (cart.length === 0) {
        showMessage('Your cart is empty', 'error');
        return;
    }

    if (!mockUser.isLoggedIn) {
        showMessage('Please log in to proceed with checkout', 'error');
        return;
    }

    const total = getCartTotal();
    
    // Simulate payment processing
    setTimeout(() => {
        showMessage(`Order placed successfully! Total: $${total.toFixed(2)}`, 'success');
        console.log('Order details:', {
            items: cart,
            total: total,
            user: mockUser.email
        });
        
        // Clear cart after successful order
        cart = [];
        cartCount = 0;
        updateCartCount();
    }, 2000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('Pharmacy website initialized');
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Initialize cart count
    updateCartCount();

    // Handle dropdown menus
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', function() {
            const content = this.querySelector('.dropdown-content');
            if (content) {
                content.style.display = 'block';
            }
        });

        dropdown.addEventListener('mouseleave', function() {
            const content = this.querySelector('.dropdown-content');
            if (content) {
                content.style.display = 'none';
            }
        });
    });

    // Handle user menu actions
    document.querySelectorAll('.dropdown-content a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#logout') {
                e.preventDefault();
                logout();
            } else if (href === '#profile') {
                e.preventDefault();
                showMessage('Profile page would open here', 'success');
            } else if (href === '#orders') {
                e.preventDefault();
                showMessage('Orders page would open here', 'success');
            } else if (href === '#prescriptions') {
                e.preventDefault();
                showMessage('Prescriptions page would open here', 'success');
            }
        });
    });

    // Handle cart click
    const cartLink = document.querySelector('.cart-link');
    if (cartLink) {
        cartLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (cart.length === 0) {
                showMessage('Your cart is empty', 'error');
            } else {
                showMessage(`You have ${cartCount} item(s) in your cart`, 'success');
                console.log('Cart contents:', cart);
            }
        });
    }
});

// Add fadeOut animation to CSS dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }
`;
document.head.appendChild(style);

// Export functions for potential use in other modules
window.PharmacyApp = {
    searchProducts,
    addToCart,
    removeFromCart,
    updateCartItemQuantity,
    getCartTotal,
    checkout,
    login,
    logout,
    uploadPrescription,
    requestRefill,
    cart,
    products
};