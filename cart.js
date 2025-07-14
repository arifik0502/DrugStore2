// cart.js - Frontend cart functionality

class CartManager {
    constructor() {
        this.baseUrl = 'api/cart.php';
        this.token = localStorage.getItem('auth_token');
        this.init();
    }
    
    init() {
        this.updateCartCount();
        this.bindEvents();
    }
    
    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart') || e.target.closest('.add-to-cart')) {
                e.preventDefault();
                const button = e.target.closest('.add-to-cart') || e.target;
                const productId = button.dataset.productId;
                const quantity = button.dataset.quantity || 1;
                this.addToCart(productId, quantity);
            }
        });
        
        // Cart item quantity changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.cart-quantity')) {
                const productId = e.target.dataset.productId;
                const quantity = parseInt(e.target.value);
                this.updateCartItem(productId, quantity);
            }
        });
        
        // Remove from cart
        document.addEventListener('click', (e) => {
            if (e.target.matches('.remove-from-cart') || e.target.closest('.remove-from-cart')) {
                e.preventDefault();
                const button = e.target.closest('.remove-from-cart') || e.target;
                const productId = button.dataset.productId;
                this.removeFromCart(productId);
            }
        });
        
        // Clear cart
        document.addEventListener('click', (e) => {
            if (e.target.matches('.clear-cart')) {
                e.preventDefault();
                this.clearCart();
            }
        });
    }
    
    async addToCart(productId, quantity = 1) {
        if (!this.token) {
            this.showMessage('Please login to add items to cart', 'error');
            return;
        }
        
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity)
                })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showMessage(data.message, 'success');
                this.updateCartCount();
            } else {
                this.showMessage(data.error, 'error');
            }
        } catch (error) {
            this.showMessage('Error adding item to cart', 'error');
        }
    }
    
    async updateCartItem(productId, quantity) {
        if (!this.token) return;
        
        try {
            const response = await fetch(`${this.baseUrl}/${productId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({ quantity })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.updateCartDisplay();
                this.updateCartCount();
            } else {
                this.showMessage(data.error, 'error');
            }
        } catch (error) {
            this.showMessage('Error updating cart', 'error');
        }
    }
    
    async removeFromCart(productId) {
        if (!this.token) return;
        
        try {
            const response = await fetch(`${this.baseUrl}/${productId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showMessage(data.message, 'success');
                this.updateCartDisplay();
                this.updateCartCount();
            } else {
                this.showMessage(data.error, 'error');
            }
        } catch (error) {
            this.showMessage('Error removing item', 'error');
        }
    }
    
    async clearCart() {
        if (!this.token) return;
        
        if (!confirm('Are you sure you want to clear your cart?')) return;
        
        try {
            const response = await fetch(`${this.baseUrl}/clear`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                this.showMessage(data.message, 'success');
                this.updateCartDisplay();
                this.updateCartCount();
            } else {
                this.showMessage(data.error, 'error');
            }
        } catch (error) {
            this.showMessage('Error clearing cart', 'error');
        }
    }
    
    async getCart() {
        if (!this.token) return null;
        
        try {
            const response = await fetch(this.baseUrl, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (response.ok) {
                return await response.json();
            }
        } catch (error) {
            console.error('Error fetching cart:', error);
        }
        return null;
    }
    
    async updateCartCount() {
        if (!this.token) {
            this.setCartCount(0);
            return;
        }
        
        try {
            const response = await fetch(`${this.baseUrl}/count`, {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.setCartCount(data.count);
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }
    
    setCartCount(count) {
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
            element.style.display = count > 0 ? 'inline' : 'none';
        });
    }
    
    async updateCartDisplay() {
        const cartContainer = document.getElementById('cart-items');
        if (!cartContainer) return;
        
        const cartData = await this.getCart();
                if (!cartData || !cartData.items || cartData.items.length === 0) {
            cartContainer.innerHTML = '<p>Your cart is empty.</p>';
            this.setCartCount(0);
            return;
        }

        cartContainer.innerHTML = '';

        cartData.items.forEach(item => {
            const itemRow = document.createElement('div');
            itemRow.className = 'cart-item flex items-center justify-between border-b py-2';

            itemRow.innerHTML = `
                <div class="flex items-center gap-4">
                    <div>
                        <h4 class="font-semibold">${item.name}</h4>
                        <p class="text-sm text-gray-500">${item.category_name}</p>
                        ${item.requires_prescription ? '<span class="text-xs text-red-500">Prescription required</span>' : ''}
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <input type="number" class="cart-quantity w-16 border rounded px-2 py-1" 
                           value="${item.quantity}" min="1" max="${item.stock_quantity}" 
                           data-product-id="${item.product_id}">
                    <span class="font-semibold">$${(item.price * item.quantity).toFixed(2)}</span>
                    <button class="remove-from-cart text-red-500 hover:underline" 
                            data-product-id="${item.product_id}">Remove</button>
                </div>
            `;

            cartContainer.appendChild(itemRow);
        });

        // Optionally update cart total
        const cartTotalElem = document.getElementById('cart-total');
        if (cartTotalElem) {
            cartTotalElem.textContent = `Total: $${cartData.total.toFixed(2)}`;
        }

        this.setCartCount(cartData.item_count);
    }

    showMessage(message, type = 'info') {
        const messageBox = document.getElementById('cart-message');
        if (!messageBox) return;

        messageBox.textContent = message;
        messageBox.className = `text-sm my-2 ${type === 'error' ? 'text-red-600' : 'text-green-600'}`;
        
        setTimeout(() => {
            messageBox.textContent = '';
            messageBox.className = '';
        }, 3000);
    }
}

// Initialize cart manager
document.addEventListener('DOMContentLoaded', () => {
    window.cartManager = new CartManager();
});
