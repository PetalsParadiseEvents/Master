// ═══════════════════════════════════════════════════════════
// INLINE SVG ICON SYSTEM — Replaces feather-icons CDN entirely
// Zero forced-reflow: SVGs are injected synchronously, no layout reads
// ═══════════════════════════════════════════════════════════
const ICONS = {
  'menu':          '<polyline points="3 12 21 12"/><polyline points="3 6 21 6"/><polyline points="3 18 21 18"/>',
  'x':             '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
  'shopping-bag':  '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/>',
  'chevron-down':  '<polyline points="6 9 12 15 18 9"/>',
  'grid':          '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
  'check':         '<polyline points="20 6 9 17 4 12"/>',
  'check-circle':  '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
  'minus':         '<line x1="5" y1="12" x2="19" y2="12"/>',
  'coffee':        '<path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>',
  'home':          '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
  'image':         '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
  'type':          '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>',
  'zap':           '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
  'award':         '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
  'facebook':      '<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>',
  'instagram':     '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
  'message-circle':'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
  'message-square':'<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>',
  'phone':         '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 01.22 1.22 2 2 0 012.22 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.16 6.16l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>',
  'mail':          '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
  'map-pin':       '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  'search':        '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
  'zoom-in':       '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>',
  'arrow-right':   '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
  'help-circle':   '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
  'shopping-bag2': '<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>',
  'trash-2':       '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>',
  'truck':         '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
  'info':          '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
  'star':          '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
};

/**
 * Returns an inline SVG string for the given icon name.
 * Accepts optional width/height/style/class overrides via the element's attributes.
 */
function renderIcon(name, opts = {}) {
  const paths = ICONS[name];
  if (!paths) return '';
  const w = opts.width  || opts.w || 16;
  const h = opts.height || opts.h || 16;
  const cls = opts.class ? ` class="${opts.class}"` : '';
  const style = opts.style ? ` style="${opts.style}"` : '';
  const sw = opts.strokeWidth || 2;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round"${cls}${style} aria-hidden="true">${paths}</svg>`;
}

/**
 * Scans a root element (default: document) for [data-feather] elements and
 * replaces them with inline SVGs. No layout reads = zero forced reflow.
 */
function replaceFeatherIcons(root = document) {
  root.querySelectorAll('[data-feather]').forEach(el => {
    const name = el.getAttribute('data-feather');
    const style = el.getAttribute('style') || '';
    // Extract width/height from inline style if present
    const wMatch = style.match(/width:\s*([\d.]+)px/);
    const hMatch = style.match(/height:\s*([\d.]+)px/);
    const w = wMatch ? wMatch[1] : 16;
    const h = hMatch ? hMatch[1] : 16;
    const sw = el.getAttribute('stroke-width') || 2;
    const cls = el.getAttribute('class') || '';
    const paths = ICONS[name];
    if (!paths) return;
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', w);
    svg.setAttribute('height', h);
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', sw);
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    if (style) svg.setAttribute('style', style);
    if (cls) svg.setAttribute('class', cls);
    svg.innerHTML = paths;
    el.replaceWith(svg);
  });
}

// Shim: make feather.replace() a no-op to avoid errors if anything still calls it
window.feather = { replace: replaceFeatherIcons };

// State Management
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let appliedPromo = JSON.parse(localStorage.getItem('appliedPromo')) || null;
let fulfillmentMethod = localStorage.getItem('fulfillmentMethod') || 'Pickup';
let rentalDays = parseInt(localStorage.getItem('rentalDays')) || 1;
let searchQuery = '';
let selectedCategory = 'All';

function getCart() {
    try {
        const stored = JSON.parse(localStorage.getItem('cart'));
        if (Array.isArray(stored)) {
            cart = stored;
        }
    } catch (e) {}
    return cart || [];
}

function saveCart(skipRouter = false) {
    localStorage.setItem('cart', JSON.stringify(cart));
    localStorage.setItem('appliedPromo', JSON.stringify(appliedPromo));
    localStorage.setItem('fulfillmentMethod', fulfillmentMethod);
    localStorage.setItem('rentalDays', rentalDays);
    updateCartBadge();
    if (!skipRouter) {
        if (window.location.hash === '#cart' || window.location.hash === '#checkout') {
            if (typeof router === 'function') router(true);
        } else if (window.location.hash === '#rentals') {
            if (typeof refreshRentalsUI === 'function') refreshRentalsUI();
        }
    }
}

window.handleSearch = (e) => {
    // Handle both input events and form submit events
    const val = e.target.value !== undefined ? e.target.value : (e.target.querySelector ? e.target.querySelector('input').value : searchQuery);
    // Sanitize: strip HTML tags before storing
    searchQuery = val.replace(/<[^>]*>/g, '').toLowerCase();
    if (window.refreshRentalsUI) window.refreshRentalsUI();
};

window.changeQty = (id, change) => updateQuantity(id, change);
window.setQty = (id, qty) => setQuantity(id, qty);
window.removeItem = (id) => removeFromCart(id);
window.handleClearCart = () => clearCart();
window.goToCheckout = (e) => {
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const currentCart = getCart();
    if (!currentCart || currentCart.length === 0) {
        showToast('Your cart is empty. Add items before checking out!');
        return;
    }
    window.location.hash = '#checkout';
    if (typeof router === 'function') router();
};
window.setRentalDays = (days) => {
    rentalDays = parseInt(days) || 1;
    if (rentalDays < 1) rentalDays = 1;
    saveCart();
};

window.refreshRentalsUI = () => {
    const grid = document.getElementById('rentals-grid');
    if (!grid) return;
    
    const filtered = rentalItems.filter(item => {
        // Category filtering
        if (selectedCategory && selectedCategory !== 'All') {
            if (item.category !== selectedCategory) return false;
        }

        const title = item.title.toLowerCase();
        const desc = (item.desc || "").toLowerCase();
        const category = (item.category || "").toLowerCase();
        const q = searchQuery.toLowerCase().trim();

        if (!q) return true;

        // Direct match
        if (title.includes(q) || desc.includes(q) || category.includes(q)) return true;

        // Plural fallback: if search is "chairs", also check for "chair"
        if (q.endsWith('s') && q.length > 3) {
            const singular = q.slice(0, -1);
            if (title.includes(singular) || desc.includes(singular) || category.includes(singular)) return true;
        }

        return false;
    });

    if (filtered.length === 0) {
        // Escape displayed query to prevent XSS
        const safeQuery = searchQuery.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
                <i data-feather="search" style="width: 48px; height: 48px; color: var(--border-color); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-secondary);">No items found for &#8220;${safeQuery}&#8221;</h3>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">Try searching for something else or browse all categories.</p>
            </div>
        `;
        if (window.feather) feather.replace();
        return;
    }

    grid.innerHTML = filtered.map(item => {
        const cartItem = cart.find(i => i.id === item.id);
        const qty = cartItem ? cartItem.quantity : 0;
        
        let actionHtml = '';
        if (qty > 0) {
            actionHtml = `
                <div class="quantity-controls" style="background: var(--bg-color); border: 1px solid var(--border-color); display: inline-flex;">
                    <button class="quantity-btn" onclick="changeQty(${item.id}, -1)" aria-label="Decrease quantity for ${item.title.replace(/"/g, '&quot;')}">-</button>
                    <input type="number" min="0" value="${qty}" aria-label="Quantity for ${item.title.replace(/"/g, '&quot;')}" style="width: 40px; text-align: center; background: transparent; border: none; color: var(--text-primary); font-family: var(--font-family); font-size: 1rem; -moz-appearance: textfield;" onchange="setQty(${item.id}, this.value)">
                    <button class="quantity-btn" onclick="changeQty(${item.id}, 1)" aria-label="Increase quantity for ${item.title.replace(/"/g, '&quot;')}">+</button>
                </div>
            `;
        } else {
            actionHtml = `<button class="btn btn-primary" style="width: 100%;" onclick="handleAddToCart(${item.id})">Add to cart</button>`;
        }

        let priceDisplay = typeof item.price === 'number' ? `$${item.price}` : item.price;
        let priceStyle = '';
        if (item.id === 4) {
            priceDisplay = `$2.00 (<30)<br/>$1.50 (30+)`;
            priceStyle = 'font-size: 0.85em; line-height: 1.2; text-align: left;';
        }

        return `
            <div class="card product-card">
                <div class="card-img-wrapper" style="position: relative; cursor: pointer;" onclick="document.getElementById('image-modal').classList.add('active'); document.getElementById('modal-img').src='${item.img}'; document.body.style.overflow='hidden';">
                    <img loading="lazy" src="${item.img}" alt="${item.title}">
                    <div class="quick-view-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s; color: white; font-weight: bold; font-size: 1.1rem; border-radius: 12px 12px 0 0;">
                        <i data-feather="zoom-in" style="margin-right: 8px;"></i> Quick View
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="card-title">${item.title}</h3>
                    <p class="card-desc" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">${item.desc || ''}</p>
                    <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                        <span class="price" style="${priceStyle}">${priceDisplay}</span>
                        <div id="action-controls-${item.id}">${actionHtml}</div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    if (window.feather) feather.replace();

    const stickyCart = document.getElementById('sticky-view-cart');
    if (stickyCart) {
        if (cart.length > 0) {
            stickyCart.style.display = 'block';
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const btn = stickyCart.querySelector('a');
            if (btn) btn.textContent = `View Cart (${totalItems} item${totalItems !== 1 ? 's' : ''})`;
        } else {
            stickyCart.style.display = 'none';
        }
    }
};

function showToast(message, showCartLink = false) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    let html = `<i data-feather="check-circle" style="color: var(--primary-color)"></i> <span>${message}</span>`;
    if (showCartLink) {
        html += ` <a href="#cart" style="color: var(--primary-color); margin-left: auto; font-weight: bold; text-decoration: underline;">View Cart</a>`;
    }
    toast.innerHTML = html;

    container.appendChild(toast);
    if (window.feather) feather.replace();

    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

function addToCart(item) {
    const existing = cart.find(i => i.id === item.id);
    if (existing) {
        existing.quantity += 1;
        showToast(`1 more ${item.title} added to cart. Total: ${existing.quantity}`, true);
    } else {
        cart.push({ ...item, quantity: 1 });
        showToast(`Added ${item.title} to cart!`, true);
    }
    saveCart();
}

function getItemPrice(item) {
    if (item.id === 4 && item.quantity >= 30) {
        return 1.5;
    }
    return item.price;
}

function getItemTotal(item) {
    return getItemPrice(item) * item.quantity * rentalDays;
}

function getCartTotal() {
    return cart.reduce((sum, item) => sum + getItemTotal(item), 0);
}

function getDynamicRecommendations() {
    if (cart.length === 0) return rentalItems.slice(0, 5);

    const cartTitles = cart.map(item => item.title.toLowerCase());
    const cartCategories = [];
    
    if (cartTitles.some(t => t.includes('grad'))) cartCategories.push('grad');
    if (cartTitles.some(t => t.includes('baby') || t.includes('seemantham'))) cartCategories.push('baby');
    if (cartTitles.some(t => t.includes('wedding') || t.includes('centerpiece'))) cartCategories.push('wedding');
    if (cartTitles.some(t => t.includes('neon'))) cartCategories.push('neon');

    let recommendations = rentalItems.filter(item => {
        const inCart = cart.some(c => c.id === item.id);
        if (inCart) return false;

        const title = item.title.toLowerCase();
        return cartCategories.some(cat => {
            if (cat === 'grad') return title.includes('grad') || title.includes('marquee');
            if (cat === 'baby') return title.includes('baby') || title.includes('seemantham') || title.includes('chair');
            if (cat === 'wedding') return title.includes('wedding') || title.includes('centerpiece') || title.includes('tent');
            if (cat === 'neon') return title.includes('neon') || title.includes('vibes');
            return false;
        });
    });

    if (recommendations.length < 4) {
        const general = rentalItems.filter(item => 
            !cart.some(c => c.id === item.id) && 
            !recommendations.some(r => r.id === item.id)
        );
        recommendations = [...recommendations, ...general];
    }

    return recommendations.slice(0, 5);
}

function getDiscount() {
    if (!appliedPromo) return 0;
    const subtotal = getCartTotal();
    const promo = PROMOS[appliedPromo];
    if (promo && subtotal >= promo.min) {
        return promo.discount;
    }
    return 0;
}

const PROMOS = {
    'PETALS5': { min: 100, discount: 5 },
    'PETALS10': { min: 150, discount: 10 },
    'PETALS15': { min: 200, discount: 15 },
    'PETALS20': { min: 300, discount: 20 }
};

function applyPromoCode(code) {
    const subtotal = getCartTotal();
    const promo = PROMOS[code.toUpperCase()];
    if (promo) {
        if (subtotal >= promo.min) {
            appliedPromo = code.toUpperCase();
            saveCart();
            showToast(`Promo ${appliedPromo} applied! $${promo.discount} off.`);
        } else {
            showToast(`Min. order for ${code} is $${promo.min}. (Excl. delivery)`);
        }
    } else {
        showToast('Invalid promo code.');
    }
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    saveCart();
}

function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?')) {
        cart = [];
        saveCart();
        showToast('Cart cleared.');
    }
}

function updateQuantity(id, change) {
    const item = cart.find(i => i.id === id);
    if (item) {
        const oldQty = item.quantity;
        item.quantity += change;
        if (item.quantity <= 0) {
            const itemName = item.title;
            removeFromCart(id);
            showToast(`Removed ${itemName} from cart.`);
        } else {
            saveCart();
            const diff = Math.abs(change);
            if (change > 0) {
                showToast(`${diff} item${diff > 1 ? 's' : ''} added for ${item.title}. Total: ${item.quantity}`, true);
            } else {
                showToast(`${diff} item${diff > 1 ? 's' : ''} removed for ${item.title}. Total: ${item.quantity}`);
            }
        }
    }
}

function updateCartBadge() {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        badge.textContent = totalItems;
        badge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

function setQuantity(id, newQty) {
    const qty = parseInt(newQty, 10);
    if (isNaN(qty)) return;

    const item = cart.find(i => i.id === id);
    if (!item) return;

    if (qty <= 0) {
        const itemName = item.title;
        removeFromCart(id);
        showToast(`Removed ${itemName} from cart.`);
        return;
    }

    const oldQty = item.quantity;
    if (qty === oldQty) return;

    item.quantity = qty;
    saveCart();
    
    const diff = Math.abs(qty - oldQty);
    if (qty > oldQty) {
        showToast(`${diff} more ${item.title} added to cart. Total: ${qty}`, true);
    } else {
        showToast(`${diff} ${item.title} removed from cart. Total: ${qty}`);
    }
}

// Data
const rentalItems = [
    { id: 1, title: 'Round Fold-In-Half Table', price: 12, img: './wp-content/uploads/2025/05/Screen-Shot-2025-05-12-at-5.19.35-PM.png', desc: 'Perfect Table to match any occasion and theme (60" x 29.8").' },
    { id: 2, title: 'Cocktail Table (With Cloths)', price: 11, img: './wp-content/uploads/2025/12/image.png', desc: 'Elegant cocktail tables with black/white cloths.' },
    { id: 3, title: 'Adult Rectangular Folding Table Rental', price: 8, img: './wp-content/uploads/2025/04/Tables.webp', desc: 'Perfect seating to match any occasion and theme.' },
    { id: 4, title: 'Adult Folding Chair', price: 2, img: './wp-content/uploads/2025/04/Chairs.webp', desc: 'Perfect seating for any occasion. Note: Bulk discount! $1.50 each when renting 30 or more.' },
    { id: 5, title: 'Wedding Tent (16x26)', price: 150, img: './wp-content/uploads/2026/04/image.png', desc: 'Light weight yet sturdy, high quality yet affordable tent.' },
    { id: 25, title: 'Tent (10x20)', price: 100, img: './wp-content/uploads/2026/05/tent-10x20.jpg', desc: '10x20 ft canopy tent, perfect for outdoor events, graduations, and parties.' },
    { id: 6, title: 'Round Cylinder Pedestal Display', price: 30, img: './wp-content/uploads/2025/05/Screen-Shot-2025-05-11-at-9.53.07-PM.png', desc: 'Set of 5 pedestals with gold/white covers for grand displays.' },
    { id: 7, title: 'Buffet Food Warmers', price: 10, img: './wp-content/uploads/2025/04/Buffet-Food-Warmers.webp', desc: 'Let your guests enjoy every bite at the perfect temperature.' },
    { id: 8, title: 'Loveseat for rental', price: 100, img: './wp-content/uploads/2026/03/IMG_1048-scaled.jpg', desc: 'Perfect for parties, baby showers, weddings, and more. Visit our website to explore our full range of rental items.' },
    { id: 9, title: 'Elegant Hand-Carved Accent Chair', price: 75, img: './wp-content/uploads/2025/12/IMG_0755-1-scaled.jpg', desc: 'Perfect as accent seating for special event décor or baby showers.' },
    { id: 10, title: 'Haldi Urli`s', price: 125, img: './wp-content/uploads/2025/09/image-edited.png', desc: 'Haldi / Maiyan Tub / Urli. Vibrant and traditional backdrops.' },
    { id: 11, title: 'Pipe and Drape Backdrop Stand', price: 50, img: './wp-content/uploads/2025/04/image-10.png', desc: 'Heavy Duty Double Crossbar Stand for Trade Shows and Decor.' },
    { id: 12, title: 'GRAD Marquee Letters', price: 40, img: './wp-content/uploads/2025/12/image-1.png', desc: 'Lighted GRAD marquee letters for rent.' },
    { id: 13, title: '4FT Marquee Numbers', price: 20, img: './wp-content/uploads/2025/07/image-7.png', desc: 'Giant Marquee Numbers for birthdays, anniversaries, or graduations.' },
    { id: 14, title: 'Photo/Any Event Backdrop', price: 150, img: './wp-content/uploads/2025/06/image-2.png', desc: 'Celebrate the beauty of your Mehendi ceremony with our elegant and artistic backdrops, perfect for creating a stunning photo-worthy setting.' },
    { id: 15, title: 'New Born Baby Photo Prop', price: 20, img: './wp-content/uploads/2025/05/Baby-backdrop-1-scaled.jpg', desc: 'Dreamy Moon Swing Photo Prop for cozy gatherings.' },
    { id: 16, title: 'Custom Graduation Setup', price: 'Varies', img: './wp-content/uploads/2026/07/graddecor 2026.jpg', desc: 'Personalized graduation decor setup tailored to your school colors and theme. Contact us for a quote.' },
    { id: 17, title: 'Premium GRAD Decor', price: 'Varies', img: './wp-content/uploads/2025/07/IMG_9901-1-scaled.jpg', desc: 'Exquisite graduation celebration setup with premium backdrops and floral arrangements. Price varies by request.' },
    { id: 18, title: 'Seemantham/Baby Shower Backdrop', price: 150, img: './wp-content/uploads/2025/07/Seemantham-2.jpg', desc: 'Traditional Seemantham or Baby Shower backdrop for your special occasion.' },
    { id: 19, title: 'VEVOR Metal Wedding Centerpiece (2PCS)', price: 25, img: './wp-content/uploads/2025/04/image-19-908x1024.png', desc: 'Gold crystal metal centerpiece (55cm / 21.65\") - Set of 2 pieces.' },
    { id: 20, title: 'Happy Birthday Neon Sign', price: 10, img: './wp-content/uploads/2025/05/HBD.jpg', desc: 'Bright and festive Happy Birthday neon sign to light up your party.' },
    { id: 21, title: 'Good Vibes Only Neon Sign', price: 10, img: './wp-content/uploads/2025/07/image-5.png', desc: 'Trendy \"Good Vibes Only\" neon sign for a modern event feel.' },
    { id: 22, title: 'Congrats Grad Neon Sign', price: 10, img: './wp-content/uploads/2025/07/81i8bvay0GL._AC_SX679_.jpg', desc: 'Celebratory Congrats Grad neon sign - perfect for Class of 2026 parties!' },
    { id: 23, title: 'Mehandi Umbrella Set', price: 3, img: './wp-content/uploads/2025/05/Umbrella.jpg', desc: 'Colorful traditional umbrellas for Mehandi or festive ceremonies ($3 each).' },
    { id: 24, title: 'Easel for Rent', price: 10, img: './wp-content/uploads/2025/09/gold-litton-lane-boards-easels-27391-64_600.jpg', desc: 'Elegant gold easel for displaying welcome signs or photos.' }
];

// Dynamically categorize rental items for advanced sidebar & filter bar
rentalItems.forEach(item => {
    const title = item.title.toLowerCase();
    if (title.includes('chair') || title.includes('loveseat')) {
        item.category = 'Chairs';
    } else if (title.includes('table')) {
        item.category = 'Tables';
    } else if (title.includes('tent')) {
        item.category = 'Tents';
    } else if (title.includes('warmer') || title.includes('buffet')) {
        item.category = 'Buffet Sets';
    } else if (title.includes('backdrop') || title.includes('stand') || title.includes('photo prop') || title.includes('swing')) {
        item.category = 'Backdrops';
    } else if (title.includes('marquee') || title.includes('number') || title.includes('letter')) {
        item.category = 'Marquee Letters';
    } else if (title.includes('neon')) {
        item.category = 'Neon Signs';
    } else {
        item.category = 'Traditional Decor';
    }
});

const services = [
    { title: 'Wedding Party', desc: 'From breathtaking floral arrangements to elegant backdrops, we craft the perfect ambiance for your special day.', img: 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&w=800&q=80' },
    { title: 'HouseWarming', desc: 'Elegant décor and personalized styling for your housewarming party, creating a warm and welcoming ambiance.', img: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80' },
    { title: 'Birthday Party', desc: 'From vibrant balloon garlands to elegant tablescapes, we create magical setups tailored to your theme.', img: './wp-content/uploads/2026/08/1st_birthday_pic.jpg' },
    { title: 'Baby Shower', desc: 'Dreamy and elegant décor featuring soft pastels, enchanting backdrops, and custom-themed setups.', img: 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=800&q=80' },
    { title: 'Haldi / Mehandi Parties', desc: 'Vibrant and traditional décor with beautiful backdrops, marigold arrangements, and artistic seating for your special ceremonies.', img: './wp-content/uploads/2025/05/Haldi-backdrop-3-2-scaled.jpg' },
    { title: 'Retirement Party', desc: 'Celebrate a career milestone with sophisticated décor, backdrop displays, and custom balloon arrangements.', img: 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=800&q=80' },
    { title: 'Festival Parties & Events', desc: 'Celebrate Diwali, Eid, Christmas, or any festive occasion with our specialized themed décor and lighting solutions.', img: 'https://images.unsplash.com/photo-1504196606672-aef5c9cefc92?auto=format&fit=crop&w=800&q=80' }
];

// Rendering Layout
function renderBanner() {
    const banner = document.getElementById('top-banner');
    if (!banner) return;

    banner.innerHTML = `
        <div class="promo-banner">
            <div class="ticker-wrap">
                <div class="ticker">
                    <span class="ticker-item">☀️ SUMMER CELEBRATION SPECIAL ☀️</span>
                    <span class="ticker-item">✨ CELEBRATE EVERY MOMENT THIS SUMMER! ✨</span>
                    <span class="ticker-item">🌸 WEDDINGS • BIRTHDAYS • BABY SHOWERS • CORPORATE EVENTS 🌸</span>
                    <span class="ticker-item">📞 QUESTIONS? CALL US AT +1 848-448-6993</span>
                    <span class="ticker-item">🚚 WE OFFER DELIVERY & PICKUP OPTIONS! 🚚</span>
                    <!-- Duplicate for seamless loop -->
                    <span class="ticker-item">☀️ SUMMER CELEBRATION SPECIAL ☀️</span>
                    <span class="ticker-item">✨ CELEBRATE EVERY MOMENT THIS SUMMER! ✨</span>
                    <span class="ticker-item">🌸 WEDDINGS • BIRTHDAYS • BABY SHOWERS • CORPORATE EVENTS 🌸</span>
                    <span class="ticker-item">📞 QUESTIONS? CALL US AT +1 848-448-6993</span>
                </div>
            </div>
        </div>
    `;
}

function renderNavbar() {
    const nav = document.getElementById('navbar');
    nav.innerHTML = `
        <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open navigation menu">
            <i data-feather="menu"></i>
        </button>
        <a href="#" class="logo-text">Petals Paradise Events</a>
        <div class="nav-links">
            <a href="#" class="nav-link">Home</a>
            <a href="#rentals" class="nav-link">Rentals</a>
            <a href="#services" class="nav-link">Services</a>
            <a href="#gallery" class="nav-link">Gallery</a>
            <a href="#videos" class="nav-link">Videos</a>
            <a href="#contact" class="nav-link">Contact Us</a>
            <a href="#contact" class="nav-link nav-plan-btn">✨ Plan My Event</a>
        </div>
        <a href="#cart" class="cart-icon-container" aria-label="View cart with ${cart.reduce((sum, item) => sum + item.quantity, 0)} items">
            <i data-feather="shopping-bag"></i>
            <span id="cart-badge" class="cart-badge">0</span>
        </a>
        
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

        <!-- Mobile Left Nav Menu Sidebar -->
        <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile navigation">
            <button class="mobile-menu-close" id="mobile-menu-close" aria-label="Close navigation menu">
                <i data-feather="x"></i>
            </button>
            <a href="#" class="nav-link">Home</a>
            
            <!-- Rentals Section with Expandable Inventory Categories -->
            <div class="menu-item-with-submenu">
                <div class="submenu-toggle-wrapper">
                    <a href="#rentals" class="nav-link">Rentals</a>
                    <button class="submenu-toggle-btn" id="rentals-toggle" aria-label="Toggle rentals categories">
                        <i data-feather="chevron-down"></i>
                    </button>
                </div>
                <div class="submenu-container" id="rentals-submenu">
                    <a href="#rentals" data-category="All" class="submenu-link"><i data-feather="grid"></i> All Rentals</a>
                    <a href="#rentals" data-category="Chairs" class="submenu-link"><i data-feather="check"></i> Chairs</a>
                    <a href="#rentals" data-category="Tables" class="submenu-link"><i data-feather="minus"></i> Tables</a>
                    <a href="#rentals" data-category="Buffet Sets" class="submenu-link"><i data-feather="coffee"></i> Buffet Sets</a>
                    <a href="#rentals" data-category="Tents" class="submenu-link"><i data-feather="home"></i> Tents</a>
                    <a href="#rentals" data-category="Backdrops" class="submenu-link"><i data-feather="image"></i> Backdrops</a>
                    <a href="#rentals" data-category="Marquee Letters" class="submenu-link"><i data-feather="type"></i> Marquees</a>
                    <a href="#rentals" data-category="Neon Signs" class="submenu-link"><i data-feather="zap"></i> Neon Signs</a>
                    <a href="#rentals" data-category="Traditional Decor" class="submenu-link"><i data-feather="award"></i> Traditional</a>
                </div>
            </div>

            <a href="#services" class="nav-link">Services</a>
            <a href="#gallery" class="nav-link">Gallery</a>
            <a href="#videos" class="nav-link">Videos</a>
            <a href="#contact" class="nav-link">Contact Us</a>
            <a href="#contact" class="nav-link mobile-plan-btn" style="color: var(--primary-color); font-weight: 700; margin-top: 0.5rem; display: inline-block;">✨ Plan My Event</a>
        </div>
    `;
    feather.replace();
    updateCartBadge();


    // Toggle Mobile Menu
    const menuBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('mobile-menu-close');
    const mobileMenu = document.getElementById('mobile-menu');
    const overlay = document.getElementById('mobile-menu-overlay');

    if (menuBtn && closeBtn && mobileMenu && overlay) {
        const openMenu = () => {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        const closeMenu = () => {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        menuBtn.addEventListener('click', openMenu);
        closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);
        
        // Close menu on link click
        mobileMenu.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', closeMenu);
        });

        // Toggle Rentals categories accordion
        const rentalsToggle = document.getElementById('rentals-toggle');
        const rentalsSubmenu = document.getElementById('rentals-submenu');
        if (rentalsToggle && rentalsSubmenu) {
            rentalsToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                rentalsToggle.classList.toggle('open');
                rentalsSubmenu.classList.toggle('open');
            });
        }

        // Submenu category selection links
        mobileMenu.querySelectorAll('.submenu-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const cat = link.getAttribute('data-category');
                window.selectRentalsCategory(cat);
                closeMenu();
            });
        });
    }
}

function renderFooter() {
    const footer = document.getElementById('footer');
    footer.innerHTML = `
        <div class="footer-grid">
            <div class="footer-col">
                <h3>Petals Paradise Events</h3>
                <p>Crafting Unforgettable Moments with elegant decor and personalized touches for every occasion in the DMV area.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/people/Petals-Paradise-Events/61574977307091/" target="_blank" rel="noopener noreferrer" aria-label="Visit our Facebook page">
                       <i data-feather="facebook"></i>
                    </a>

                    <a href="https://www.instagram.com/petalsparadiseevents/" target="_blank" rel="noopener noreferrer" aria-label="Visit our Instagram profile">
                       <i data-feather="instagram"></i>
                    </a>

                    <a href="https://wa.me/qr/UGD3LZ3UNUCQP1" target="_blank" rel="noopener noreferrer" aria-label="Contact us on WhatsApp">
                       <i data-feather="message-circle"></i>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Quick Links</h3>
                <a href="#">Home</a>
                <a href="#rentals">Rentals</a>
                <a href="#services">Services</a>
                <a href="#gallery">Gallery</a>
            </div>
            <div class="footer-col">
                <h3>Service Areas</h3>
                <a href="./locations/">All Service Areas</a>
                <a href="./locations/aldie-va.html">Aldie, VA</a>
                <a href="./locations/ashburn-va.html">Ashburn, VA</a>
                <a href="./locations/leesburg-va.html">Leesburg, VA</a>
                <a href="./locations/loudoun-county-va.html">Loudoun County, VA</a>
            </div>
            <div class="footer-col">
                <h3>Contact Us</h3>
                <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;"><i data-feather="phone" style="width: 20px; height: 20px; flex-shrink: 0; color: var(--primary-color);"></i> <a href="tel:+18484486993" class="phone-link" style="color: inherit; text-decoration: none; line-height: 1;">+1 848-448-6993</a></p>
                <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;"><i data-feather="mail" style="width: 20px; height: 20px; flex-shrink: 0; color: var(--primary-color);"></i> <a href="mailto:contact@petalsparadiseevents.com" style="color: inherit; text-decoration: none; word-break: break-word; line-height: 1;">contact@petalsparadiseevents.com</a></p>
                <p style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;"><i data-feather="map-pin" style="width: 20px; height: 20px; flex-shrink: 0; color: var(--primary-color);"></i> <span style="line-height: 1;"><a href="./locations/" style="color: inherit; text-decoration: none; margin: 0; display: inline;">Serving Ashburn, Aldie & DMV Area</a></span></p>
            </div>
        </div>
        <div class="footer-bottom" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem; margin-top: 1.5rem;">
            <p style="margin: 0;">&copy; ${new Date().getFullYear()} Petals Paradise Events. All Rights Reserved.</p>
            <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                <a href="#privacy" style="color: var(--text-secondary); text-decoration: none; transition: var(--transition);" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">Privacy Policy</a>
                <span style="color: var(--border-color);">|</span>
                <a href="#terms" style="color: var(--text-secondary); text-decoration: none; transition: var(--transition);" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">Terms of Service</a>
                <span style="color: var(--border-color);">|</span>
                <a href="#cookies" style="color: var(--text-secondary); text-decoration: none; transition: var(--transition);" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">Cookie Policy</a>
            </div>
        </div>
    `;
    feather.replace();
}

// Views
function renderHome() {
    return `
        <!-- HERO -->
        <section class="hero">
            <div class="hero-content">
                <div class="hero-eyebrow">🌸 Serving Loudoun County &amp; the DMV Since 2025</div>
                <h1>Your Celebration Partner,<br>For Every Chapter of Life</h1>
                <p>From your baby's first birthday to your dream wedding — Petals Paradise Events is with you at every milestone. One team. One call. Every celebration.</p>
                <div class="hero-btns">
                    <a href="#rentals" class="btn btn-primary">Explore Rentals</a>
                    <a href="#contact" class="btn btn-outline">✨ Plan My Event</a>
                </div>
            </div>
        </section>

        <!-- LIFE STAGES JOURNEY TIMELINE -->
        <section class="life-stages-section">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title">We're With You At Every Milestone</h2>
                    <p class="section-subtitle">Families return to us year after year — because life never stops celebrating.</p>
                </div>
                <div class="life-stages-track">
                    <div class="life-stages-scroll">
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🤰</span><span class="life-stage-label">Baby Shower</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🎂</span><span class="life-stage-label">1st Birthday</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🎈</span><span class="life-stage-label">Birthdays</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card life-stage-highlight"><span class="life-stage-icon">💫</span><span class="life-stage-label">Sweet 16</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🌺</span><span class="life-stage-label">Haldi / Mehndi</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🎓</span><span class="life-stage-label">Graduation</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">💍</span><span class="life-stage-label">Bridal Shower</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card life-stage-highlight"><span class="life-stage-icon">💒</span><span class="life-stage-label">Wedding</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🏠</span><span class="life-stage-label">Housewarming</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card"><span class="life-stage-icon">🎉</span><span class="life-stage-label">Anniversary</span></a>
                        <div class="life-stage-arrow">→</div>
                        <a href="#contact" class="life-stage-card life-stage-highlight"><span class="life-stage-icon">🌴</span><span class="life-stage-label">Retirement</span></a>
                    </div>
                </div>
                <div class="text-center" style="margin-top: 2rem;">
                    <a href="#contact" class="btn btn-primary">Start Planning Your Next Milestone →</a>
                </div>
            </div>
        </section>

        <!-- HOW WE WORK WITH YOU -->
        <section class="how-we-work-section">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title">How We Work With You</h2>
                    <p class="section-subtitle">Three simple steps to your perfect celebration.</p>
                </div>
                <div class="how-we-work-grid">
                    <div class="how-step">
                        <div class="how-step-number">01</div>
                        <div class="how-step-icon">💬</div>
                        <h3>Tell Us Your Vision</h3>
                        <p>Share your occasion, guest count, budget and ideas. Whether you have a full vision or just a theme in mind — we'll guide you from there.</p>
                    </div>
                    <div class="how-step-connector">→</div>
                    <div class="how-step">
                        <div class="how-step-number">02</div>
                        <div class="how-step-icon">🎨</div>
                        <h3>We Build Your Plan</h3>
                        <p>Our team designs your décor, sources the right pieces, coordinates logistics and creates a timeline so nothing is left to chance.</p>
                    </div>
                    <div class="how-step-connector">→</div>
                    <div class="how-step">
                        <div class="how-step-number">03</div>
                        <div class="how-step-icon">🥂</div>
                        <h3>You Simply Celebrate</h3>
                        <p>Arrive to a beautifully transformed space. Our team handles setup, coordinates vendors and manages breakdown — you enjoy every moment.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- THREE SERVICE TIERS -->
        <section class="tiers-section">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-title">Choose Your Experience</h2>
                    <p class="section-subtitle">Whether you need beautiful décor or want us to handle everything — we have the right package for you.</p>
                </div>
                <div class="tiers-grid">
                    <div class="tier-card">
                        <div class="tier-badge">✨ Décor Only</div>
                        <div class="tier-icon">🎀</div>
                        <h3>Décor &amp; Rentals</h3>
                        <p class="tier-tagline">Beautiful décor, delivered and set up.</p>
                        <ul class="tier-features">
                            <li>✅ Premium rental inventory</li>
                            <li>✅ Professional setup &amp; installation</li>
                            <li>✅ Balloon garlands &amp; backdrops</li>
                            <li>✅ Centerpieces &amp; tablescapes</li>
                            <li>✅ Teardown &amp; pickup</li>
                        </ul>
                        <p class="tier-best-for">Best for: DIY planners who need stunning décor without the planning work.</p>
                        <a href="#rentals" class="btn btn-outline" style="width:100%; text-align:center; margin-top: auto;">Browse Rentals</a>
                    </div>
                    <div class="tier-card tier-card-featured">
                        <div class="tier-featured-label">Most Popular</div>
                        <div class="tier-badge">🌸 Planning + Décor</div>
                        <div class="tier-icon">🌸</div>
                        <h3>Planning &amp; Décor</h3>
                        <p class="tier-tagline">We plan it. We design it. You enjoy it.</p>
                        <ul class="tier-features">
                            <li>✅ Everything in Décor Only</li>
                            <li>✅ Personal event consultation</li>
                            <li>✅ Theme &amp; color palette design</li>
                            <li>✅ Vendor referral network</li>
                            <li>✅ Event timeline creation</li>
                            <li>✅ Day-of coordination support</li>
                        </ul>
                        <p class="tier-best-for">Best for: Busy families who want help planning but don't need full event management.</p>
                        <a href="#contact" class="btn btn-primary" style="width:100%; text-align:center; margin-top: auto;">Get a Quote</a>
                    </div>
                    <div class="tier-card">
                        <div class="tier-badge">👑 Full-Service</div>
                        <div class="tier-icon">👑</div>
                        <h3>Full Celebration Management</h3>
                        <p class="tier-tagline">Say "we have an event" — we handle the rest.</p>
                        <ul class="tier-features">
                            <li>✅ Everything in Planning + Décor</li>
                            <li>✅ Dedicated event planner</li>
                            <li>✅ Venue research &amp; coordination</li>
                            <li>✅ Full vendor booking &amp; management</li>
                            <li>✅ Guest management support</li>
                            <li>✅ Complete event-day management</li>
                        </ul>
                        <p class="tier-best-for">Best for: Families who want a completely stress-free, premium celebration experience.</p>
                        <a href="#contact" class="btn btn-outline" style="width:100%; text-align:center; margin-top: auto;">Inquire Now</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURED SERVICES -->
        <section class="container mt-2">
            <div class="text-center">
                <h2 class="section-title">Events We Specialize In</h2>
                <p class="section-subtitle">From intimate baby showers to grand weddings — we bring every vision to life.</p>
            </div>
            <div class="grid">
                ${services.map(s => `
                    <div class="card">
                        <div class="card-img-wrapper">
                            <img loading="lazy" src="${s.img}" alt="${s.title}">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">${s.title}</h3>
                            <p class="card-desc">${s.desc}</p>
                            <a href="#contact" class="btn btn-outline" style="width:100%; text-align:center;" aria-label="Plan a ${s.title}">Plan This Event</a>
                        </div>
                    </div>
                `).join('')}
            </div>
        </section>

        <!-- ABOUT -->
        <section class="about-section">
            <div class="container">
                <div class="about-grid">
                    <div class="about-text">
                        <h2 class="section-title" style="text-align: left;">More Than Décor.<br>We're Your Celebration Partner.</h2>
                        <p style="font-size: 1.05rem; line-height: 1.9; color: var(--text-secondary); margin-bottom: 1.5rem;">
                            We're not just a décor company. We're the team families in Ashburn, Aldie, and across the DMV call for every celebration — year after year. Our goal isn't just to make your event look beautiful; it's to become the trusted partner your family relies on for every chapter of life.
                        </p>
                        <p style="font-size: 1.05rem; line-height: 1.9; color: var(--text-secondary); margin-bottom: 2rem;">
                            When a customer says <em>"My daughter is turning 16. Around 100 guests. She wants something elegant, but I don't know where to start"</em> — we say <strong style="color: var(--primary-color);">"We'll take care of it."</strong>
                        </p>
                        <div class="about-stats">
                            <div class="about-stat">
                                <span class="about-stat-num">200+</span>
                                <span class="about-stat-label">Events Decorated</span>
                            </div>
                            <div class="about-stat">
                                <span class="about-stat-num">50+</span>
                                <span class="about-stat-label">Rental Items</span>
                            </div>
                            <div class="about-stat">
                                <span class="about-stat-num">DMV</span>
                                <span class="about-stat-label">Wide Coverage</span>
                            </div>
                        </div>
                        <a href="#contact" class="btn btn-primary" style="margin-top: 1.5rem;">Start Your Journey With Us →</a>
                    </div>
                    <div class="about-promise">
                        <div class="promise-card">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; font-size: 1.2rem;">The Petals Paradise Promise</h3>
                            <div class="promise-item">
                                <span class="promise-icon">🤝</span>
                                <div><strong>One Point of Contact</strong><p>You call us. We coordinate everything. No juggling 10 different vendors.</p></div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">🎯</span>
                                <div><strong>Tailored To Your Vision</strong><p>Every event is designed around your unique style, culture and budget.</p></div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">✨</span>
                                <div><strong>Premium Quality, Always</strong><p>We maintain high-quality inventory and never compromise on the details.</p></div>
                            </div>
                            <div class="promise-item">
                                <span class="promise-icon">🔄</span>
                                <div><strong>A Relationship, Not a Transaction</strong><p>We celebrate your milestones with you — for years to come.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    `;
}


function refreshRentalsUI() {
    rentalItems.forEach(item => {
        const container = document.getElementById(`action-controls-${item.id}`);
        if (container) {
            const cartItem = cart.find(i => i.id === item.id);
            const qty = cartItem ? cartItem.quantity : 0;
            if (qty > 0) {
                container.innerHTML = `
                    <div class="quantity-controls" style="background: var(--bg-color); border: 1px solid var(--border-color); display: inline-flex;">
                        <button class="quantity-btn" onclick="changeQty(${item.id}, -1)">-</button>
                        <input type="number" min="0" value="${qty}" style="width: 40px; text-align: center; background: transparent; border: none; color: var(--text-primary); font-family: var(--font-family); font-size: 1rem; -moz-appearance: textfield;" onchange="setQty(${item.id}, this.value)">
                        <button class="quantity-btn" onclick="changeQty(${item.id}, 1)">+</button>
                    </div>
                `;
            } else {
                container.innerHTML = `<button class="btn btn-primary" onclick="handleAddToCart(${item.id})">Add to Cart</button>`;
            }
        }
    });

    const stickyCart = document.getElementById('sticky-view-cart');
    if (stickyCart) {
        if (cart.length > 0) {
            stickyCart.style.display = 'block';
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const btn = stickyCart.querySelector('a');
            if (btn) btn.textContent = `View Cart (${totalItems} item${totalItems !== 1 ? 's' : ''})`;
        } else {
            stickyCart.style.display = 'none';
        }
    }
}

// Category filter logic
window.selectRentalsCategory = (category, element) => {
    selectedCategory = category;
    
    // Update category pills if currently rendered on page
    document.querySelectorAll('.category-pill').forEach(pill => {
        const text = pill.childNodes[0].textContent.trim();
        if (text === category) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });

    // Update active state of submenu links in the left nav
    document.querySelectorAll('.submenu-link').forEach(link => {
        if (link.getAttribute('data-category') === category) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Smooth scroll the selected pill to center on mobile
    if (element && typeof element.scrollIntoView === 'function') {
        element.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    if (window.location.hash !== '#rentals') {
        window.location.hash = '#rentals';
    } else {
        if (window.refreshRentalsUI) window.refreshRentalsUI();
    }
};

function renderRentals() {
    window.handleAddToCart = (id) => {
        const item = rentalItems.find(i => i.id === id);
        if (item) addToCart(item);
    };

    const categories = ['All', 'Chairs', 'Tables', 'Buffet Sets', 'Tents', 'Backdrops', 'Marquee Letters', 'Neon Signs', 'Traditional Decor'];

    // Calculate dynamic counts
    const counts = {};
    categories.forEach(cat => {
        if (cat === 'All') {
            counts[cat] = rentalItems.length;
        } else {
            counts[cat] = rentalItems.filter(item => item.category === cat).length;
        }
    });

    // Trigger initial refresh
    setTimeout(() => {
        if (window.refreshRentalsUI) window.refreshRentalsUI();
        
        // Sync active state of submenu link
        document.querySelectorAll('.submenu-link').forEach(link => {
            if (link.getAttribute('data-category') === selectedCategory) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // Smooth scroll the active category pill into center on load
        const activePill = document.querySelector('.category-pill.active');
        if (activePill && typeof activePill.scrollIntoView === 'function') {
            activePill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }

        // Hide swipe prompt dynamically upon scroll interaction
        const bar = document.querySelector('.category-filter-bar');
        const prompt = document.getElementById('filter-scroll-prompt');
        if (bar && prompt) {
            bar.addEventListener('scroll', function hidePrompt() {
                if (bar.scrollLeft > 15) {
                    prompt.style.opacity = '0';
                    prompt.style.transform = 'translateY(5px)';
                    setTimeout(() => {
                        prompt.style.display = 'none';
                    }, 500);
                    // Unbind listener immediately
                    bar.removeEventListener('scroll', hidePrompt);
                }
            });
        }
    }, 50);

    return `
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Rentals Collection</h2>
                <p class="section-subtitle">Browse our premium selection of event decor and party essentials.</p>
            </div>

            <!-- Search Bar -->
            <div style="max-width: 600px; margin: 0 auto 1.5rem;">
                <form onsubmit="event.preventDefault(); window.handleSearch(event);" style="position: relative;">
                    <i data-feather="search" style="position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); width: 20px;"></i>
                    <input type="text" 
                        placeholder="Search for backdrops, marquee letters, neon signs..." 
                        class="form-control" 
                        style="padding-left: 3.5rem; border-radius: 50px; height: 60px; font-size: 1.1rem; box-shadow: var(--shadow-sm); border: 2px solid var(--border-color);"
                        oninput="window.handleSearch(event)"
                        value="${searchQuery}">
                    <button type="submit" style="display: none;"></button>
                </form>
            </div>

            <!-- Categories Filter Bar -->
            <div class="category-filter-bar">
                ${categories.map(cat => {
                    const isActive = selectedCategory === cat;
                    const count = counts[cat] || 0;
                    return `
                        <button onclick="window.selectRentalsCategory('${cat}', this)" class="category-pill ${isActive ? 'active' : ''}">
                            ${cat} <span class="pill-count">${count}</span>
                        </button>
                    `;
                }).join('')}
            </div>

            <!-- Swipe/Scroll Assistant Indicator -->
            <div class="filter-scroll-prompt" id="filter-scroll-prompt">
                <span>Swipe left / right for more categories</span> <i data-feather="arrow-right" class="no-zoom" style="width: 14px; height: 14px; stroke-width: 2.5px;"></i>
            </div>

            <div id="rentals-grid" class="grid">
                <div style="grid-column: 1/-1; text-align: center; padding: 2rem;">Loading collection...</div>
            </div>

            <div class="empty-state mt-2">
                <i data-feather="help-circle"></i>
                <h3>Can't find what you're looking for?</h3>
                <p style="color: var(--text-secondary); margin-top:1rem;">If there's a specific item you need for your event that isn't listed here, please let us know! We are constantly updating our inventory.</p>
                <a href="#contact" class="btn btn-outline mt-2">Inquire About Missing Items</a>
            </div>

            <!-- Sticky Cart Button -->
            <div id="sticky-view-cart" class="sticky-checkout-container" style="display: none;">
                <a href="#cart" class="btn btn-primary" style="width: 100%; text-align:center;">View Cart</a>
            </div>
        </div>
    `;
}

function renderServices() {
    return `
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">Comprehensive décor and planning solutions tailored for every chapter of your life.</p>
            </div>

            <!-- Service Tiers -->
            <div class="tiers-grid" style="margin-bottom: 4rem;">
                <div class="tier-card">
                    <div class="tier-badge">✨ Tier 1</div>
                    <div class="tier-icon">🎀</div>
                    <h3>Décor &amp; Rentals</h3>
                    <p class="tier-tagline">Beautiful décor, delivered and set up.</p>
                    <ul class="tier-features">
                        <li>✅ Premium rental inventory</li>
                        <li>✅ Professional setup &amp; installation</li>
                        <li>✅ Balloon garlands &amp; backdrops</li>
                        <li>✅ Centerpieces &amp; tablescapes</li>
                        <li>✅ Teardown &amp; pickup</li>
                    </ul>
                    <a href="#rentals" class="btn btn-outline" style="width:100%; text-align:center; margin-top: auto;">Browse Rentals</a>
                </div>
                <div class="tier-card tier-card-featured">
                    <div class="tier-featured-label">Most Popular</div>
                    <div class="tier-badge">🌸 Tier 2</div>
                    <div class="tier-icon">🌸</div>
                    <h3>Planning &amp; Décor</h3>
                    <p class="tier-tagline">We plan it. We design it. You enjoy it.</p>
                    <ul class="tier-features">
                        <li>✅ Everything in Décor Only</li>
                        <li>✅ Personal event consultation</li>
                        <li>✅ Theme &amp; color palette design</li>
                        <li>✅ Vendor referral network</li>
                        <li>✅ Event timeline creation</li>
                        <li>✅ Day-of coordination support</li>
                    </ul>
                    <a href="#contact" class="btn btn-primary" style="width:100%; text-align:center; margin-top: auto;">Get a Quote</a>
                </div>
                <div class="tier-card">
                    <div class="tier-badge">👑 Tier 3</div>
                    <div class="tier-icon">👑</div>
                    <h3>Full Celebration Management</h3>
                    <p class="tier-tagline">Say "we have an event" — we handle the rest.</p>
                    <ul class="tier-features">
                        <li>✅ Everything in Planning + Décor</li>
                        <li>✅ Dedicated event planner</li>
                        <li>✅ Venue research &amp; coordination</li>
                        <li>✅ Full vendor booking &amp; management</li>
                        <li>✅ Guest management support</li>
                        <li>✅ Complete event-day management</li>
                    </ul>
                    <a href="#contact" class="btn btn-outline" style="width:100%; text-align:center; margin-top: auto;">Inquire Now</a>
                </div>
            </div>

            <!-- Event Types -->
            <div class="text-center" style="margin-bottom: 2rem;">
                <h2 class="section-title" style="font-size: 1.8rem;">Events We Celebrate</h2>
                <p class="section-subtitle">Every occasion deserves to be extraordinary.</p>
            </div>
            <div class="grid">
                ${services.map(s => `
                    <div class="card">
                        <div class="card-img-wrapper">
                            <img loading="lazy" src="${s.img}" alt="${s.title}" onerror="this.onerror=null;this.src='https://via.placeholder.com/300?text=Image+Not+Found'"/>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">${s.title}</h3>
                            <p class="card-desc">${s.desc}</p>
                            <a href="#contact" class="btn btn-outline" style="width:100%; text-align:center;">Plan This Event</a>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}


function renderGallery() {
    const gallery = {
        "Birthday Parties": [
            "./wp-content/uploads/2026/08/1st_birthday_pic.jpg",
            "./wp-content/uploads/2025/12/IMG_0670-2-scaled.jpg",
            "./wp-content/uploads/2025/09/IMG_0012-scaled.jpg"
        ],
        "Graduation 2026": [
            "./wp-content/uploads/2026/07/graddecor 2026.jpg",
            "./wp-content/uploads/2025/07/IMG_9906-scaled.jpg",
            "./wp-content/uploads/2025/07/IMG_9901-1-scaled.jpg",
            "./wp-content/uploads/2025/07/image-2-scaled.png"
        ],
        "Traditional & Seemantham": [
            "./wp-content/uploads/2025/07/Seemantham-4.jpg",
            "./wp-content/uploads/2025/05/Haldi-backdrop-3-2-scaled.jpg",
            "./wp-content/uploads/2025/05/Baby-backdrop-1-scaled.jpg",
            "./wp-content/uploads/2025/05/backdrop-2-1-scaled.jpg",
            "./wp-content/uploads/2025/05/31782321-EE45-4F6A-9482-25F39324F8B7-scaled.jpg",
            "./wp-content/uploads/2025/07/IMG_9925-1-scaled.jpg"
        ],
        "Event Highlights": [
            "./wp-content/uploads/2026/05/IMG_1314.jpeg",
            "./wp-content/uploads/2025/09/IMG_0048-scaled.jpg",
            "./wp-content/uploads/2025/09/IMG_0079-scaled.jpg",
            "./wp-content/uploads/2025/12/IMG_0755-2-scaled.jpg",
            "./wp-content/uploads/2025/07/image-1.png",
            "./wp-content/uploads/2025/07/image-scaled.png"
        ]
    };

    window.currentGalleryCategory = window.currentGalleryCategory || 'All';
    window.filterGallery = (category) => {
        window.currentGalleryCategory = category;
        const container = document.getElementById('gallery-container');
        if (container) {
            container.outerHTML = renderGallery();
            if (window.feather) feather.replace();
            
            // Re-bind image modal clicks for new content
            const modal = document.getElementById('image-modal');
            const modalImg = document.getElementById('modal-img');
            document.querySelectorAll('#gallery-container img:not(.no-zoom)').forEach(img => {
                img.onclick = function() {
                    modal.classList.add('active');
                    modalImg.src = this.src;
                    document.body.style.overflow = 'hidden';
                };
            });
        }
    };

    const categories = ['All', ...Object.keys(gallery)];
    
    let renderedContent = '';
    Object.entries(gallery).forEach(([category, images]) => {
        if (window.currentGalleryCategory !== 'All' && window.currentGalleryCategory !== category) return;
        
        renderedContent += `
            <div class="mt-2 gallery-category-block">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary-color); border-left: 4px solid var(--primary-color); padding-left: 1rem;">${category}</h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                    ${images.map(img => `
                        <div class="card" style="padding: 0; overflow: hidden; border-radius: 12px; height: 350px;">
                            <img loading="lazy" src="${img}" alt="${category}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; cursor: zoom-in;" onerror="this.onerror=null;this.src='https://via.placeholder.com/300?text=Image+Not+Found'" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    });

    return `
        <div class="container" id="gallery-container">
            <div class="text-center">
                <h2 class="section-title">Our Gallery</h2>
                <p class="section-subtitle">A glimpse into the stunning events we've brought to life.</p>
            </div>
            
            <div class="gallery-filters" style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem;">
                ${categories.map(cat => `
                    <button onclick="filterGallery('${cat}')" class="btn ${window.currentGalleryCategory === cat ? 'btn-primary' : 'btn-outline'}" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">${cat}</button>
                `).join('')}
            </div>

            ${renderedContent}
        </div>
    `;
}

function renderVideos() {
    const videos = [
        { url: "./wp-content/uploads/2026/08/Summer Vibes.mp4", title: "Summer Vibes 🌞" },
        { url: "./wp-content/uploads/2026/07/Washington DC Fireworks.mov", title: "Washington DC Fireworks 🎆" },
        { url: "./wp-content/uploads/2025/09/Wedding-Set-up-@dcwarmemorial-petalsparadiseevents-eventrentals-eventdecor-weddingsetup-dcw.mp4", title: "Wedding Set-up @ DC War Memorial" },
        { url: "./wp-content/uploads/2025/12/IMG_0560.mov", title: "Elegant Event Highlight" },
        { url: "./wp-content/uploads/2025/09/Winnie-the-Pooh-Themed-Birthday-DecorThank-you-@tdupexperience-It-was-great-collaborating-with-y.mp4", title: "Winnie the Pooh Themed Birthday" },
        { url: "./wp-content/uploads/2025/09/Bridal-Shower-Decorpetalsparadiseevents-eventdecor-eventrentals-babyshowerdecor-birthdaydec.mp4", title: "Bridal Shower Decor" },
        { url: "./wp-content/uploads/2025/07/Seemantham-Video-1.mp4", title: "Seemantham Celebration" },
        { url: "./wp-content/uploads/2025/07/July-4th-Video-1.mp4", title: "July 4th Celebration" },
        { url: "./wp-content/uploads/2025/07/Bday-Decor-Video.mp4", title: "Birthday Party Setup" },
        { url: "./wp-content/uploads/2025/07/Baby-Shower-Video.mp4", title: "Baby Shower Highlights" },
        { url: "./wp-content/uploads/2025/07/Seemantham-Video.mp4", title: "Traditional Seemantham" },
        { url: "./wp-content/uploads/2025/06/7BB8EC83-E840-4082-9C8D-C2B664F3C78Asegment_video_2.mp4", title: "Grand Event Setup" },
        { url: "./wp-content/uploads/2025/06/0B6B56B8-F9AD-466F-B758-DEC86D60898Dsegment_video_1.mp4", title: "Ceremony Backdrop" },
        { url: "./wp-content/uploads/2025/06/Sharing-some-recent-event-setups-we-had-pleasure-meeting-some-nice-people-🫰1030-tent-available-for-rent.eventrental-event-decor.mp4", title: "Recent Tent & Decor Setups" },
        { url: "./wp-content/uploads/2025/06/House-warming-decoreventdecor-eventrentals-petalsparadiseevents-babyshower.mp4", title: "House Warming Decor" },
        { url: "./wp-content/uploads/2025/06/Birthday-Decor.Please-contact-us-for-any-event-decorations.birthdaydecor-babyshower-graduationparty🎓-sweet16.mp4", title: "Dream Birthday Decorations" },
        { url: "./wp-content/uploads/2025/06/Baby-Shower-Decor-🎈eventdecoration-babyshowerdecor-petalsparadiseevents-eventrentals.mp4", title: "Deluxe Baby Shower Decor" }
    ];

    return `
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Videos Gallery</h2>
                <p class="section-subtitle">Experience the magic of our event transformations through these highlights.</p>
            </div>
            
            <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2rem;">
                ${videos.map(v => `
                    <div class="card" style="padding: 0.5rem; background: var(--surface-color); overflow: hidden; border-radius: 16px;">
                        <div style="aspect-ratio: 9/16; width: 100%; border-radius: 12px; overflow: hidden; background: #000; display: flex; align-items: center;">
                            <video controls preload="metadata" style="width: 100%; height: auto; display: block;">
                                <source src="${v.url}">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <div style="padding: 1rem 0.5rem;">
                            <h4 style="color: var(--primary-color); font-size: 0.95rem; margin-bottom: 0.2rem;">${v.title}</h4>
                            <p style="color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">#petalsparadiseevents</p>
                        </div>
                    </div>
                `).join('')}
            </div>

            <div class="text-center mt-2">
                <a href="https://www.instagram.com/petalsparadiseevents/" target="_blank" class="btn btn-outline">
                    <i data-feather="instagram" style="width: 16px; margin-right: 8px; vertical-align: middle;"></i> View More on Instagram
                </a>
            </div>
        </div>
    `;
}

function renderContact() {
    window.handleContactSubmit = (e) => {
        e.preventDefault();
        const form = e.target;
        const name = form.querySelector('#contact-name').value;
        const email = form.querySelector('#contact-email').value;
        const phone = form.querySelector('#contact-phone')?.value || '';
        const eventType = form.querySelector('#contact-event-type')?.value || '';
        const serviceTier = form.querySelector('#contact-service-tier')?.value || '';
        const guestCount = form.querySelector('#contact-guest-count')?.value || '';
        const eventDate = form.querySelector('#contact-event-date')?.value || '';
        const budget = form.querySelector('#contact-budget')?.value || '';
        const msg = form.querySelector('#contact-msg').value;

        const leadNotes = `Event: ${eventType}\nService Level: ${serviceTier}\nDate: ${eventDate}\nGuests: ${guestCount}\nBudget: ${budget}\nPhone: ${phone}\n\nDetails:\n${msg}`;

        // Save lead confidentially to backend API
        fetch('/api/lead.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                email: email,
                phone: phone,
                event_type: eventType,
                service_tier: serviceTier,
                guest_count: guestCount,
                event_date: eventDate,
                budget: budget,
                notes: leadNotes,
                source: 'Website Plan My Event Form'
            })
        }).catch(err => console.error("Lead logging error:", err));

        const subject = encodeURIComponent(`New Event Inquiry: ${eventType || 'General'} - ${name}`);
        const body = encodeURIComponent(`Name: ${name}\nEmail: ${email}\nPhone: ${phone}\nEvent Type: ${eventType}\nService Level: ${serviceTier}\nEvent Date: ${eventDate}\nGuest Count: ${guestCount}\nBudget: ${budget}\n\nVision & Details:\n${msg}`);
        
        // Open mailto as fallback
        window.location.href = `mailto:contact@petalsparadiseevents.com?subject=${subject}&body=${body}`;

        form.reset();
        showToast('Thank you! Your inquiry has been submitted confidentially.');
    };

    return `
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">✨ Plan Your Celebration</h2>
                <p class="section-subtitle">Tell us about your event. Whether you need décor rentals or full planning — we're here to help.</p>
            </div>
            <div class="cart-layout">
                <div class="cart-summary" style="position: static;">
                    <form onsubmit="handleContactSubmit(event)">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" id="contact-name" class="form-control" placeholder="Your Full Name" required>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" id="contact-email" class="form-control" placeholder="name@example.com" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" id="contact-phone" class="form-control" placeholder="(703) 555-0199">
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label">Event Type / Milestone *</label>
                                <select id="contact-event-type" class="form-control" required>
                                    <option value="">Select Milestone...</option>
                                    <option value="Baby Shower / Seemantham">🤰 Baby Shower / Seemantham</option>
                                    <option value="1st Birthday / Cradle Ceremony">🎂 1st Birthday / Cradle Ceremony</option>
                                    <option value="Birthday Party">🎈 Birthday Party</option>
                                    <option value="Sweet 16 / Quinceañera">💫 Sweet 16 / Quinceañera</option>
                                    <option value="Haldi / Mehndi / Sangeet">🌺 Haldi / Mehndi / Sangeet</option>
                                    <option value="Graduation Party">🎓 Graduation Party</option>
                                    <option value="Bridal Shower / Engagement">💍 Bridal Shower / Engagement</option>
                                    <option value="Wedding / Reception">💒 Wedding / Reception</option>
                                    <option value="Housewarming (Griha Pravesh)">🏠 Housewarming (Griha Pravesh)</option>
                                    <option value="Anniversary">🎉 Anniversary</option>
                                    <option value="Retirement Party">🌴 Retirement Party</option>
                                    <option value="Corporate / Community Event">🏢 Corporate / Community Event</option>
                                    <option value="Other Celebration">✨ Other Celebration</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Desired Service Tier *</label>
                                <select id="contact-service-tier" class="form-control" required>
                                    <option value="">Select Package...</option>
                                    <option value="Tier 1: Décor Only / Rentals">✨ Tier 1: Décor Only & Rentals</option>
                                    <option value="Tier 2: Planning + Décor">🌸 Tier 2: Planning & Décor (Recommended)</option>
                                    <option value="Tier 3: Full Celebration Management">👑 Tier 3: Full Celebration Management</option>
                                    <option value="Not Sure Yet / Need Advice">❓ Not Sure Yet - Need Advice</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row-3">
                            <div class="form-group">
                                <label class="form-label">Event Date</label>
                                <input type="date" id="contact-event-date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estimated Guests</label>
                                <select id="contact-guest-count" class="form-control">
                                    <option value="Under 25">Under 25</option>
                                    <option value="25 - 50">25 - 50</option>
                                    <option value="50 - 100" selected>50 - 100</option>
                                    <option value="100 - 200">100 - 200</option>
                                    <option value="200+">200+</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estimated Budget</label>
                                <select id="contact-budget" class="form-control">
                                    <option value="Under $500">Under $500</option>
                                    <option value="$500 - $1,500" selected>$500 - $1,500</option>
                                    <option value="$1,500 - $3,000">$1,500 - $3,000</option>
                                    <option value="$3,000 - $5,000">$3,000 - $5,000</option>
                                    <option value="$5,000+">$5,000+</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tell Us About Your Vision &amp; Requirements</label>
                            <textarea id="contact-msg" class="form-control" rows="4" placeholder="Share any specific theme ideas, color preferences, venue location, or questions you have..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.05rem; padding: 14px;">✨ Submit Event Inquiry</button>
                        <p style="font-size: 0.78rem; color: var(--text-secondary); margin-top: 12px; text-align: center;">
                            🔒 <strong>Confidentiality Guarantee:</strong> We respect your privacy. Your information is strictly used for your event inquiry and exclusive Petals Paradise offer updates.
                        </p>
                    </form>
                </div>
                <div>
                    <div class="card" style="padding: 2rem; background: var(--surface-color); position: sticky; top: 100px;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">Contact Information</h3>
                        <p style="margin-bottom: 1rem;"><i data-feather="phone"></i> <a href="tel:+18484486993" style="color: inherit; text-decoration: none; font-weight: 600;">+1 848-448-6993</a></p>
                        <p style="margin-bottom: 1rem;"><i data-feather="mail"></i> <a href="mailto:contact@petalsparadiseevents.com" style="color: inherit; text-decoration: none;">contact@petalsparadiseevents.com</a></p>
                        <p style="margin-bottom: 1.5rem;"><i data-feather="map-pin"></i> Serving Ashburn, Aldie &amp; Loudoun County, VA</p>

                        <hr style="border: none; border-top: 1fr solid var(--border-color); margin: 1.5rem 0;">

                        <h4 style="color: var(--text-primary); margin-bottom: 0.8rem; font-size: 0.95rem;">Why Choose Petals Paradise?</h4>
                        <ul style="list-style: none; padding: 0; font-size: 0.88rem; line-height: 1.8; color: var(--text-secondary);">
                            <li>🌸 <strong>One Partner</strong> for all life milestones</li>
                            <li>🚚 <strong>Prompt Delivery &amp; Setup</strong> across DMV</li>
                            <li>✨ <strong>Customized</strong> to your budget &amp; style</li>
                            <li>💬 <strong>Fast Response</strong> within 24 hours</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `;
}


function renderCart() {
    window.handlePromo = (e) => {
        e.preventDefault();
        const input = e.target.querySelector('input');
        applyPromoCode(input.value);
    };
    window.quickAdd = (id) => {
        const item = rentalItems.find(i => i.id === id);
        if (item) addToCart(item);
    };
    window.setFulfillment = (method) => {
        fulfillmentMethod = method;
        saveCart(true); // Save without full re-render
        
        // Update Total Display manually to avoid flicker/scroll
        const totalDisplays = document.querySelectorAll('#summary-total-val');
        const finalTotal = getCartTotal() - getDiscount();
        const text = method === 'Delivery' ? `$${finalTotal} + Delivery (TBD)` : `$${finalTotal}`;
        
        totalDisplays.forEach(el => {
            el.innerHTML = `<span style="font-size: 1rem; color: var(--text-secondary);">Total Estimate:</span> <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color); text-align: right;">${text}</span>`;
            el.style.display = 'flex';
            el.style.justifyContent = 'space-between';
            el.style.alignItems = 'center';
            el.style.flexWrap = 'wrap';
            el.style.gap = '0.5rem';
            el.style.width = '100%';
        });
        
        // Also toggle any delivery-specific summary rows
        const deliveryRows = document.querySelectorAll('#delivery-summary-row');
        deliveryRows.forEach(el => {
            el.style.display = method === 'Delivery' ? 'flex' : 'none';
        });
    };

    window.initCartStickyObserver = (buttonId, stickyId) => {
        const btn = document.getElementById(buttonId);
        const sticky = document.getElementById(stickyId);
        if (btn && sticky) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    sticky.style.display = entry.isIntersecting ? 'none' : 'block';
                });
            }, { threshold: 0.1 });
            observer.observe(btn);
        }
    };

    setTimeout(() => window.initCartStickyObserver('cart-checkout-btn', 'sticky-checkout'), 100);

    const activeCart = getCart();
    if (activeCart.length === 0) {
        return `
            <div class="container">
                <h2 class="section-title">Your Cart</h2>
                <div class="empty-state" style="margin-bottom: 4rem;">
                    <i data-feather="shopping-bag" style="width: 64px; height: 64px; color: var(--border-color); margin-bottom: 1.5rem;"></i>
                    <h3>Your cart is empty</h3>
                    <p style="color: var(--text-secondary); margin-top:1rem;">Browse our rentals to find the perfect decor for your event.</p>
                    <a href="#rentals" class="btn btn-primary mt-2">View Rentals</a>
                </div>
                
                <div class="mt-2" style="border-top: 1px solid var(--border-color); padding-top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Recommended for Your Event</h3>
                    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                        ${getDynamicRecommendations().map(item => `
                            <div class="card recommendation-card">
                                <div class="card-img-wrapper" style="height: 150px;">
                                    <img loading="lazy" src="${item.img}" alt="${item.title}">
                                </div>
                                <div class="card-body" style="padding: 1rem;">
                                    <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; line-height: 1.2;">${item.title}</h4>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span class="price" style="font-size: 0.9rem;">$${item.price}</span>
                                        <button class="btn btn-primary" style="padding: 4px 12px; font-size: 0.75rem;" onclick="quickAdd(${item.id})">Add</button>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    const subtotal = getCartTotal();
    const discount = getDiscount();

    return `
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Your Cart</h2>
                <button class="btn btn-outline" style="border-color: #ef4444; color: #ef4444;" onclick="handleClearCart()">
                    <i data-feather="trash-2" style="width: 16px; vertical-align: middle; margin-right: 8px;"></i>Clear All
                </button>
            </div>
            <p class="section-subtitle" style="margin-bottom: 2rem; text-align: left; margin-left: 0;">Review your selected rental items below.</p>
            <div class="cart-layout">
                <div>
                    ${cart.map(item => `
                        <div class="cart-item">
                            <img loading="lazy" src="${item.img}" alt="${item.title}">
                            <div class="cart-item-info">
                                <h4 class="cart-item-title">${item.title}</h4>
                                <p style="color: var(--primary-color); font-weight:600;">$${getItemPrice(item)}</p>
                            </div>
                            <div class="cart-item-actions">
                                <div class="quantity-controls">
                                    <button class="quantity-btn" onclick="changeQty(${item.id}, -1)">-</button>
                                    <input type="number" min="0" value="${item.quantity}" style="width: 40px; text-align: center; background: transparent; border: none; color: var(--text-primary); font-family: var(--font-family); font-size: 1rem; -moz-appearance: textfield;" onchange="setQty(${item.id}, this.value)">
                                    <button class="quantity-btn" onclick="changeQty(${item.id}, 1)">+</button>
                                </div>
                                <button class="remove-btn" onclick="removeItem(${item.id})">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="cart-summary">
                    <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                    <div class="summary-row">
                        <span>Items (${cart.reduce((s, i) => s + i.quantity, 0)})</span>
                        <span>$${subtotal}</span>
                    </div>
                    ${discount > 0 ? `
                        <div class="summary-row" style="color: #10b981;">
                            <span>Discount (${appliedPromo})</span>
                            <span>-$${discount}</span>
                        </div>
                    ` : ''}
                    
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label" style="font-size: 0.85rem;">Fulfillment Method</label>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem;">
                                <input type="radio" name="cart_fulfillment" value="Pickup" ${fulfillmentMethod === 'Pickup' ? 'checked' : ''} onclick="setFulfillment('Pickup')"> Pickup
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem;">
                                <input type="radio" name="cart_fulfillment" value="Delivery" ${fulfillmentMethod === 'Delivery' ? 'checked' : ''} onclick="setFulfillment('Delivery')"> Delivery
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label class="form-label" style="font-size: 0.85rem;">Rental Duration (Days)</label>
                        <input type="number" min="1" class="form-control" value="${rentalDays}" onchange="setRentalDays(this.value)" style="margin-top: 0.5rem; width: 100px;">
                    </div>

                    ${fulfillmentMethod === 'Delivery' ? `
                        <div class="summary-row" style="margin-top: 0.5rem;">
                            <span>Delivery</span>
                            <span style="color: var(--primary-color);">TBD</span>
                        </div>
                    ` : ''}
                    
                    <form onsubmit="handlePromo(event)" style="margin-top: 1.5rem;">
                        <div class="form-group" style="display: flex; gap: 0.5rem; margin-bottom: 0;">
                            <input type="text" class="form-control" placeholder="Promo Code" value="${appliedPromo || ''}" style="margin-bottom: 0;">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </form>

                    <div id="summary-total-val" class="summary-total" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-size: 1rem; color: var(--text-secondary);">Total Estimate:</span>
                        <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color); text-align: right;">$${subtotal - discount}${fulfillmentMethod === 'Delivery' ? ' + Delivery (TBD)' : ''}</span>
                    </div>
                    <button type="button" id="cart-checkout-btn" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 1.5rem; display: block; padding: 1.2rem;" onclick="goToCheckout(event)">
                        Proceed to Checkout
                    </button>
                    <a href="#rentals" class="btn btn-outline" style="width: 100%; text-align:center; margin-top:1rem;">Continue Shopping</a>
                </div>
            </div>

            <div class="mt-2" style="border-top: 1px solid var(--border-color); padding-top: 2rem;">
                <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Complete Your Event Setup</h3>
                <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                    ${getDynamicRecommendations().map(item => `
                        <div class="card recommendation-card">
                            <div class="card-img-wrapper" style="height: 150px;">
                                <img loading="lazy" src="${item.img}" alt="${item.title}">
                            </div>
                            <div class="card-body" style="padding: 1rem;">
                                <h4 style="font-size: 0.9rem; margin-bottom: 0.5rem; line-height: 1.2;">${item.title}</h4>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="price" style="font-size: 0.9rem;">$${item.price}</span>
                                    <button class="btn btn-primary" style="padding: 4px 12px; font-size: 0.75rem;" onclick="quickAdd(${item.id})">Add</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            <!-- Sticky Checkout Button for Mobile -->
            <div id="sticky-checkout" class="sticky-checkout-container">
                <button type="button" class="btn btn-primary" style="width: 100%; text-align:center;" onclick="goToCheckout(event)">Proceed to Checkout</button>
            </div>
        </div>
    `;
}

function renderCheckout() {
    const activeCart = getCart();
    if (!activeCart || activeCart.length === 0) {
        window.location.hash = '#cart';
        return '';
    }

    window.handleOrderSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const details = Object.fromEntries(formData);

        const orderData = {
            name: details.name,
            email: details.email,
            phone: details.phone,
            date: details.date,
            location: details.location || '',
            fulfillment: details.fulfillment,
            delivery_address: details.delivery_address || '',
            special_requests: details.special_requests || '',
            pickup_date_manual: details.pickup_date_manual || '',
            pickup_time_manual: details.pickup_time_manual || '',
            dropoff_date_manual: details.dropoff_date_manual || '',
            dropoff_time_manual: details.dropoff_time_manual || '',
            delivery_date_manual: details.delivery_date_manual || '',
            delivery_time_manual: details.delivery_time_manual || '',
            collection_date_manual: details.collection_date_manual || '',
            collection_time_manual: details.collection_time_manual || '',
            subtotal: getCartTotal(),
            discount: getDiscount(),
            total: getCartTotal() - getDiscount(),
            promo_code: appliedPromo || '',
            items: cart.map(item => ({
                id: item.id,
                title: item.title,
                quantity: item.quantity,
                price: getItemPrice(item)
            }))
        };

        const submitBtn = document.getElementById('checkout-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Placing Order...';
        }

        fetch('/api/place_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Save Order ID to show on confirmation page
                localStorage.setItem('lastPlacedOrderId', data.order_id);
                localStorage.setItem('lastPlacedOrderName', details.name);
                
                // Clear cart
                cart = [];
                appliedPromo = null;
                saveCart();
                
                window.location.hash = '#confirmation';
            } else {
                alert(data.error || 'There was an issue submitting your order. Please try again.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Rental Request';
                }
            }
        })
        .catch(err => {
            console.error("Order submission error:", err);
            alert('Could not submit order. Please check your internet connection and try again.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Rental Request';
            }
        });
    };

    window.initAutocomplete = () => {
        try {
            const input = document.querySelector('input[name="delivery_address"]');
            if (!input) return;

            if (window.google && window.google.maps && window.google.maps.places && !window.googleMapsAuthFailed) {
                if (!input.hasAttribute('data-autocomplete-init')) {
                    const autocomplete = new google.maps.places.Autocomplete(input, {
                        types: ['address'],
                        componentRestrictions: { country: 'us' }
                    });
                    input.setAttribute('data-autocomplete-init', 'true');
                    
                    // Prevent 'Enter' from submitting form when selecting an autocomplete option
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                        }
                    });
                }
            } else {
                // If maps auth failed or script is missing, enforce manual address input
                input.style.backgroundImage = 'none';
                if (window.googleMapsAuthFailed) {
                    input.placeholder = "Enter full address manually";
                }
                input.disabled = false;
                input.readOnly = false;
                input.removeAttribute('disabled');
                input.removeAttribute('readonly');
                input.classList.remove('pac-target-input');
            }
        } catch (error) {
            console.error("Google Maps Autocomplete failed to initialize:", error);
        }
    };

    // Global handler for Google Maps authentication failures
    window.gm_authFailure = () => {
        window.googleMapsAuthFailed = true;
        console.warn("Google Maps authentication failed. Switching to manual address entry.");
        const input = document.querySelector('input[name="delivery_address"]');
        if (input) {
            input.style.backgroundImage = 'none';
            input.placeholder = "Enter full address manually";
            input.disabled = false;
            input.readOnly = false;
            input.removeAttribute('disabled');
            input.removeAttribute('readonly');
            input.classList.remove('pac-target-input');
        }
    };

    window.toggleDelivery = (type) => {
        const deliverySection = document.getElementById('delivery-section');
        const summaryTotal = document.getElementById('summary-total-val');
        const deliverySummary = document.getElementById('delivery-summary-row');
        
        if (deliverySection) {
            deliverySection.style.display = type === 'Delivery' ? 'block' : 'none';
            const addressInput = deliverySection.querySelector('input');
            if (addressInput) {
                addressInput.required = type === 'Delivery';
                if (type === 'Delivery') {
                    if (typeof window.loadGoogleMaps === 'function') window.loadGoogleMaps();
                    setTimeout(window.initAutocomplete, 150);
                }
            }
        }
        
        if (deliverySummary) {
            deliverySummary.style.display = type === 'Delivery' ? 'flex' : 'none';
        }
        
        if (summaryTotal) {
            const finalTotal = getCartTotal() - getDiscount();
            summaryTotal.textContent = type === 'Delivery' ? `$${finalTotal} + Delivery (TBD)` : `$${finalTotal}`;
        }

        fulfillmentMethod = type;
        saveCart(true); // Skip full router refresh to prevent jumping/flickering

        // Toggle Dedicated Logistics Blocks
        const pickupLogistics = document.getElementById('logistics-pickup');
        const deliveryLogistics = document.getElementById('logistics-delivery');
        const venueSection = document.getElementById('venue-location-section');
        
        if (venueSection) {
            venueSection.style.display = type === 'Delivery' ? 'none' : 'block';
            const venueInput = venueSection.querySelector('input');
            if (venueInput) venueInput.required = type === 'Pickup';
        }

        if (pickupLogistics && deliveryLogistics) {
            pickupLogistics.style.display = type === 'Pickup' ? 'block' : 'none';
            deliveryLogistics.style.display = type === 'Delivery' ? 'block' : 'none';
            
            // Set required status for active inputs
            pickupLogistics.querySelectorAll('input').forEach(i => i.required = type === 'Pickup');
            deliveryLogistics.querySelectorAll('input').forEach(i => i.required = type === 'Delivery');
        }
    };

    const subtotal = getCartTotal();
    const discount = getDiscount();

    return `
        <div class="container">
            <h2 class="section-title">Checkout</h2>
            <div class="cart-layout">
                <div class="cart-summary" style="position: static;">
                    <h3 style="margin-bottom: 1.5rem;">Event Details</h3>
                    <form onsubmit="handleOrderSubmit(event)">
                        <div class="form-group">
                            <label for="checkout-name" class="form-label">Full Name</label>
                            <input type="text" id="checkout-name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="checkout-email" class="form-label">Email</label>
                            <input type="email" id="checkout-email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="checkout-phone" class="form-label">Phone</label>
                            <input type="tel" id="checkout-phone" name="phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="checkout-date" class="form-label">Event Date</label>
                            <input type="date" id="checkout-date" name="date" class="form-control" required>
                        </div>
                        <div id="venue-location-section" class="form-group">
                            <label class="form-label">Venue Location (Name/City)</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Westfields Marriott or Ashburn, VA" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Fulfillment Method</label>
                            <div style="display: flex; gap: 2rem; margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="fulfillment" value="Pickup" ${fulfillmentMethod === 'Pickup' ? 'checked' : ''} onclick="toggleDelivery('Pickup')"> Pickup
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="radio" name="fulfillment" value="Delivery" ${fulfillmentMethod === 'Delivery' ? 'checked' : ''} onclick="toggleDelivery('Delivery')"> Delivery
                                </label>
                            </div>
                        </div>

                        <div id="delivery-section" style="display: ${fulfillmentMethod === 'Delivery' ? 'block' : 'none'}; border: 1px dashed var(--primary-color); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: rgba(212, 175, 55, 0.05);">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label">Delivery Address</label>
                                <input type="text" name="delivery_address" class="form-control" placeholder="Enter full address for delivery quote" ${fulfillmentMethod === 'Delivery' ? 'required' : ''}>
                                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                    <i data-feather="truck" style="width: 14px; vertical-align: middle;"></i> 
                                    Delivery fee will be based on location. Our team will contact you with the final estimate once the request is submitted.
                                </p>
                            </div>
                        </div>
                        <div id="logistics-container">
                            <!-- Dedicated Pickup Mode Fields -->
                            <div id="logistics-pickup" style="display: ${fulfillmentMethod === 'Pickup' ? 'block' : 'none'};">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Pick Up Date</label>
                                        <input type="date" name="pickup_date_manual" class="form-control" ${fulfillmentMethod === 'Pickup' ? 'required' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Pick Up Time</label>
                                        <input type="time" name="pickup_time_manual" class="form-control" ${fulfillmentMethod === 'Pickup' ? 'required' : ''}>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Return Date</label>
                                        <input type="date" name="dropoff_date_manual" class="form-control" ${fulfillmentMethod === 'Pickup' ? 'required' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Return Time</label>
                                        <input type="time" name="dropoff_time_manual" class="form-control" ${fulfillmentMethod === 'Pickup' ? 'required' : ''}>
                                    </div>
                                </div>
                            </div>

                            <!-- Dedicated Delivery Mode Fields -->
                            <div id="logistics-delivery" style="display: ${fulfillmentMethod === 'Delivery' ? 'block' : 'none'};">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Delivery Date</label>
                                        <input type="date" name="delivery_date_manual" class="form-control" ${fulfillmentMethod === 'Delivery' ? 'required' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Delivery Time</label>
                                        <input type="time" name="delivery_time_manual" class="form-control" ${fulfillmentMethod === 'Delivery' ? 'required' : ''}>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group">
                                        <label class="form-label">Collection Date</label>
                                        <input type="date" name="collection_date_manual" class="form-control" ${fulfillmentMethod === 'Delivery' ? 'required' : ''}>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Collection Time</label>
                                        <input type="time" name="collection_time_manual" class="form-control" ${fulfillmentMethod === 'Delivery' ? 'required' : ''}>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Special Requests / Missing Items</label>
                            <textarea name="special_requests" class="form-control" placeholder="Is there something specific you're looking for that's missing from our catalog? Or any other special instructions?"></textarea>
                        </div>
                        <button type="submit" id="checkout-submit-btn" class="btn btn-primary" style="width: 100%; padding: 1.2rem;">Submit Rental Request</button>
                    </form>
                </div>
                <div class="cart-summary">
                    <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                    <div class="summary-row">
                        <span>Items (${cart.reduce((s, i) => s + i.quantity, 0)})</span>
                        <span>$${subtotal}</span>
                    </div>
                    ${discount > 0 ? `
                        <div class="summary-row" style="color: #10b981;">
                            <span>Discount (${appliedPromo})</span>
                            <span>-$${discount}</span>
                        </div>
                    ` : ''}
                    <div id="delivery-summary-row" class="summary-row" style="display: none;">
                        <span>Delivery</span>
                        <span style="color: var(--primary-color);">TBD</span>
                    </div>
                    
                    <form onsubmit="handlePromo(event)" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group" style="display: flex; gap: 0.5rem; margin-bottom: 0;">
                            <input type="text" class="form-control" placeholder="Promo Code" value="${appliedPromo || ''}" style="margin-bottom: 0;">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </form>

                    <div id="summary-total-val" class="summary-total" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-size: 1rem; color: var(--text-secondary);">Total Estimate:</span>
                        <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color); text-align: right;">$${subtotal - discount}${fulfillmentMethod === 'Delivery' ? ' + Delivery (TBD)' : ''}</span>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 1rem;">
                        <i data-feather="info" style="width:16px; margin-right:4px; vertical-align:middle;"></i>
                        Call us: <a href="tel:+18484486993" class="phone-link" style="text-decoration: underline;">+1 848-448-6993</a>
                    </p>
                </div>
            </div>
        </div>
    `;
}

function renderGraduation() {
    const gradSpecific = rentalItems.filter(i => i.title.toLowerCase().includes('grad') || i.title.includes('Marquee'));
    const essentials = [
        rentalItems.find(i => i.title === 'Wedding Tent (16x26)'),
        rentalItems.find(i => i.title === 'Tent (10x20)'),
        rentalItems.find(i => i.title === 'Cocktail Table (With Cloths)'),
        rentalItems.find(i => i.title === 'Round Fold-In-Half Table'),
        rentalItems.find(i => i.title === 'Adult Folding Chair')
    ].filter(Boolean);

    return `
        <div class="container">
            <div class="text-center">
                <h2 class="section-title">Class of 2026 Graduation Collection</h2>
                <p class="section-subtitle">Make your graduation party unforgettable with our premium decor and party essentials.</p>
            </div>

            <div class="mb-2">
                <h3 style="margin-bottom: 2rem; color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Featured Graduation Decor</h3>
                <div class="grid">
                    ${gradSpecific.map(item => renderItemCard(item)).join('')}
                </div>
            </div>

            <div class="mt-2">
                <h3 style="margin-bottom: 2rem; color: var(--primary-color); border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">Party Essentials You'll Need</h3>
                <div class="grid">
                    ${essentials.map(item => renderItemCard(item)).join('')}
                </div>
            </div>
        </div>
    `;
}

function renderItemCard(item) {
    const cartItem = cart.find(i => i.id === item.id);
    const qty = cartItem ? cartItem.quantity : 0;

    let actionHtml = '';
    if (qty > 0) {
        actionHtml = `
            <div class="quantity-controls" style="background: var(--bg-color); border: 1px solid var(--border-color); display: inline-flex;">
                <button class="quantity-btn" onclick="changeQty(${item.id}, -1)">-</button>
                <input type="number" min="0" value="${qty}" style="width: 40px; text-align: center; background: transparent; border: none; color: var(--text-primary); font-family: var(--font-family); font-size: 1rem; -moz-appearance: textfield;" onchange="setQty(${item.id}, this.value)">
                <button class="quantity-btn" onclick="changeQty(${item.id}, 1)">+</button>
            </div>
        `;
    } else {
        actionHtml = `<button class="btn btn-primary" onclick="handleAddToCart(${item.id})">Add to Cart</button>`;
    }

    let priceDisplay = typeof item.price === 'number' ? `$${item.price}` : item.price;
    let priceStyle = '';
    if (item.id === 4) {
        priceDisplay = `$2.00 (<30)<br/>$1.50 (30+)`;
        priceStyle = 'font-size: 0.85em; line-height: 1.2; text-align: left;';
    } else if (item.price === 'Varies') {
        priceStyle = 'color: var(--primary-color); font-weight: 700;';
    }

    return `
        <div class="card">
            <div class="card-img-wrapper">
                <img loading="lazy" src="${item.img}" alt="${item.title}" onerror="this.onerror=null;this.src='https://via.placeholder.com/300?text=Image+Not+Found'"/>
            </div>
            <div class="card-body">
                <h3 class="card-title">${item.title}</h3>
                <p class="card-desc">${item.desc}</p>
                <div class="card-footer">
                    <span class="price" style="${priceStyle}">${priceDisplay}</span>
                    <div id="action-controls-${item.id}">${actionHtml}</div>
                </div>
            </div>
        </div>
    `;
}

// Helper for Mobile Sticky Actions
function initStickyObserver(targetId, actionText, actionFnStr) {
    const stickyContainer = document.getElementById('sticky-action-container');
    const targetBtn = document.getElementById(targetId);

    if (!stickyContainer) return;

    if (!targetBtn) {
        stickyContainer.classList.remove('visible');
        return;
    }

    // Set content
    stickyContainer.innerHTML = `<button class="btn btn-primary" onclick="${actionFnStr}">${actionText}</button>`;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Show sticky if target is NOT intersecting (off-screen)
            if (entry.isIntersecting) {
                stickyContainer.classList.remove('visible');
            } else {
                stickyContainer.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    observer.observe(targetBtn);
}

function renderConfirmation() {
    setTimeout(() => {
        if (typeof confetti === 'function') {
            confetti({
                particleCount: 150,
                spread: 70,
                origin: { y: 0.6 }
            });
        }
    }, 100);

    const orderId = localStorage.getItem('lastPlacedOrderId') || '';
    const customerName = localStorage.getItem('lastPlacedOrderName') || '';

    const orderIdDisplay = orderId 
        ? `<p style="font-family: monospace; font-size: 1.25rem; font-weight: bold; background: rgba(212, 175, 55, 0.1); border: 1px solid var(--primary-color); padding: 0.75rem 1.5rem; border-radius: 8px; display: inline-block; margin-top: 1.5rem; color: var(--primary-color);">Confirmation ID: ${orderId}</p>` 
        : '';

    return `
        <div class="container text-center" style="padding: 4rem 2rem;">
            <div style="color: var(--primary-color); margin-bottom:1rem;">
                <i data-feather="check-circle" style="width: 64px; height: 64px;"></i>
            </div>
            <h2 class="section-title">Order Placed Successfully!</h2>
            <p class="section-subtitle">Thank you${customerName ? ' ' + customerName : ''}! Your rental request has been received. Our team will review the details and contact you via email or phone within 24 hours to coordinate logistics.</p>
            ${orderIdDisplay}
            
            <div style="margin-top: 3rem; background: var(--surface-color); padding: 2.5rem; border-radius: 12px; border: 1px dashed var(--primary-color); max-width: 600px; margin-left: auto; margin-right: auto;">
                <h3 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1.5rem;">How was your experience?</h3>
                <p style="margin-bottom: 1.5rem; color: var(--text-secondary); font-size: 1.1rem;">We'd love to hear from you. Please consider leaving us a review on Google!</p>
                <a href="https://g.page/r/CXcHwjVlRTQIEBM/review" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i data-feather="star"></i> Leave a Google Review
                </a>
            </div>

            <div style="margin-top: 3rem;">
                <a href="#" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    `;
}

// Render Legal Pages
function renderPrivacy() {
    return `
        <div class="container legal-page-container">
            <h1 class="section-title text-center">Privacy Policy</h1>
            <p class="section-subtitle text-center">Last Updated: August 2026</p>
            
            <div class="legal-content-card">
                <section class="legal-section">
                    <h2>1. Introduction</h2>
                    <p>At Petals Paradise Events, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website, use our rental cart, or contact us for inquiries.</p>
                </section>

                <section class="legal-section">
                    <h2>2. Information We Collect</h2>
                    <p>We only collect personal information that you voluntarily provide to us when submitting an event inquiry, placing a rental request, or communicating with us. This information includes:</p>
                    <ul>
                        <li><strong>Contact Information:</strong> Name, email address, phone number.</li>
                        <li><strong>Event Details:</strong> Event date, estimated budget, estimated guest count, event type/milestone, and special design vision.</li>
                        <li><strong>Fulfillment Details:</strong> Delivery address, venue location, pickup/delivery date and time, and return/collection date and time.</li>
                        <li><strong>Cart Preferences:</strong> The list of party rental items you select.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>3. How We Use Your Information</h2>
                    <p>We use the collected information for the following business purposes:</p>
                    <ul>
                        <li>To review, quote, and fulfill your party rental and decor requests.</li>
                        <li>To contact you regarding logistics (delivery, setup, pickup, and collection details).</li>
                        <li>To respond to your questions, custom requests, and customer service inquiries.</li>
                        <li>To send optional promotional updates, seasonal offers, and discounts (you can opt out at any time).</li>
                        <li>To improve our website functionality, customer experience, and chatbot assistant responses.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Data Sharing and Protection</h2>
                    <p>We value your trust and guarantee that **we do not sell, rent, or trade your personal information** to third parties. We only share information with third parties when necessary to operate our business or as required by law, such as:</p>
                    <ul>
                        <li><strong>Service Providers:</strong> Google Maps Places API for address autocomplete verification during checkout.</li>
                        <li><strong>Legal Compliance:</strong> If required by law, subpoena, or to protect the safety and rights of Petals Paradise Events or others.</li>
                    </ul>
                    <p>We implement appropriate physical, technical, and administrative security measures to protect your personal data against unauthorized access, loss, or misuse.</p>
                </section>

                <section class="legal-section">
                    <h2>5. Cookies and Local Storage</h2>
                    <p>Our website uses cookies and browser local storage to provide essential services, including:</p>
                    <ul>
                        <li>Saving your rental cart items and rental duration preferences.</li>
                        <li>Storing your fulfillment method selection (Pickup vs. Delivery).</li>
                        <li>Recording your cookie consent preference settings.</li>
                    </ul>
                    <p>You can manage your cookie consent preferences at any time through our <a href="#cookies" style="color: var(--primary-color); text-decoration: underline;">Cookie Policy page</a>.</p>
                </section>

                <section class="legal-section">
                    <h2>6. Your Rights</h2>
                    <p>Depending on your location, you may have rights regarding your personal data under various data protection laws (such as GDPR or CCPA), including the right to access, correct, or request deletion of the personal data we hold about you. To exercise these rights, please contact us using the details below.</p>
                </section>

                <section class="legal-section">
                    <h2>7. Contact Us</h2>
                    <p>If you have any questions, concerns, or requests regarding this Privacy Policy, please reach out to us:</p>
                    <div class="contact-box-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <div class="card no-zoom" style="padding: 1rem; background: var(--surface-color); border: 1px solid var(--border-color); text-align: center; cursor: default;">
                            <i data-feather="mail" style="color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                            <p style="font-weight: 600; margin: 0;">Email Us</p>
                            <a href="mailto:contact@petalsparadiseevents.com" style="color: var(--primary-color); text-decoration: underline;">contact@petalsparadiseevents.com</a>
                        </div>
                        <div class="card no-zoom" style="padding: 1rem; background: var(--surface-color); border: 1px solid var(--border-color); text-align: center; cursor: default;">
                            <i data-feather="phone" style="color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                            <p style="font-weight: 600; margin: 0;">Call Us</p>
                            <a href="tel:+18484486993" style="color: var(--primary-color); text-decoration: underline;">+1 848-448-6993</a>
                        </div>
                    </div>
                </section>
            </div>
            
            <div class="text-center" style="margin-top: 2rem; margin-bottom: 4rem;">
                <a href="#" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    `;
}

function renderTerms() {
    return `
        <div class="container legal-page-container">
            <h1 class="section-title text-center">Terms of Service</h1>
            <p class="section-subtitle text-center">Last Updated: August 2026</p>
            
            <div class="legal-content-card">
                <section class="legal-section">
                    <h2>1. Scope of Agreement</h2>
                    <p>These Terms of Service govern the rental of party inventory and decoration services provided by Petals Paradise Events. By placing a rental request through our cart and checkout, you agree to comply with and be bound by these terms.</p>
                </section>

                <section class="legal-section">
                    <h2>2. Booking, Deposit, and Confirmation</h2>
                    <p>Submitting a rental request through this website **does not guarantee availability** of the requested items. Your order is not booked or confirmed until:</p>
                    <ol>
                        <li>Our team reviews your request and verifies inventory availability for your event date.</li>
                        <li>We contact you to confirm logistics and provide the final price quote (including delivery fees, if applicable).</li>
                        <li>A security deposit or full payment is received, and a formal rental agreement is signed.</li>
                    </ol>
                    <p>We reserve the right to decline rental requests for any reason, including scheduling conflicts or location constraints.</p>
                </section>

                <section class="legal-section">
                    <h2>3. Rental Period and Late Returns</h2>
                    <p>Unless agreed otherwise in writing, all rentals are for a standard duration of **1 day (24 hours)**. The customer agrees to pick up or receive delivery, and return or allow collection of the items, at the times specified during checkout or in the signed rental agreement.</p>
                    <p>Late returns are subject to a late fee of **$50 per day** plus additional rental charges for each day the items are kept past the agreed return date.</p>
                </section>

                <section class="legal-section">
                    <h2>4. Delivery, Setup, and Pick-up</h2>
                    <p>For delivery orders, delivery fees are calculated based on mileage, logistics, and setup requirements. It is the customer's responsibility to:</p>
                    <ul>
                        <li>Ensure a safe, accessible, cleared, and flat area is ready for delivery and setup.</li>
                        <li>Verify that the venue permits outside rental vendors and setup teams.</li>
                        <li>Be present (or delegate an authorized representative) at the time of delivery to inspect and sign off on the condition of the rentals.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>5. Customer Responsibility and Damages</h2>
                    <p>From the time of receipt (delivery or pickup) until the time of return (collection or drop-off), the customer assumes full custody and responsibility for the rented items. The customer agrees that:</p>
                    <ul>
                        <li>Rentals must be used only for their intended purposes and protected from severe weather (rain, high winds, extreme heat).</li>
                        <li>All items must be returned in the same clean, undamaged condition as received.</li>
                        <li>**Damages, Stains, or Loss:** If items are returned damaged, stained, broken, or are lost/stolen, the customer is liable for the full cost of professional cleaning, repair, or complete replacement of the item. Replacement fees will be deducted from the security deposit or billed directly to the customer.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>6. Cancellations and Refunds</h2>
                    <p>We understand that event plans change. Our cancellation policy is as follows:</p>
                    <ul>
                        <li><strong>14+ Days Notice:</strong> Cancellations made 14 or more days before the scheduled event date are eligible for a **100% refund** of payments made (excluding non-refundable design deposits, if applicable).</li>
                        <li><strong>7 to 13 Days Notice:</strong> Cancellations made between 7 and 13 days before the event date are eligible for a **50% refund**.</li>
                        <li><strong>Under 7 Days Notice:</strong> Cancellations made less than 7 days before the event date are **non-refundable**.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>7. Limitation of Liability</h2>
                    <p>Petals Paradise Events is not liable for any injuries, accidents, property damage, or losses resulting from the setup, use, or failure of rented items (including tents, Loveseats, tables, neon signs, Urli tubs, or folding chairs). The customer agrees to indemnify and hold harmless Petals Paradise Events from any claims arising from the rental usage.</p>
                </section>

                <section class="legal-section">
                    <h2>8. Governing Law</h2>
                    <p>These terms and any rental agreement signed with Petals Paradise Events shall be governed by and construed in accordance with the laws of the Commonwealth of Virginia, USA.</p>
                </section>
            </div>
            
            <div class="text-center" style="margin-top: 2rem; margin-bottom: 4rem;">
                <a href="#" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    `;
}

function renderCookies() {
    const consentVal = localStorage.getItem('cookie_consent') || 'Not Set';
    let statusBadge = '<span class="cookie-status-badge status-declined">Non-Essential Declined / Not Set</span>';
    if (consentVal === 'accepted') {
        statusBadge = '<span class="cookie-status-badge status-accepted">All Cookies Allowed</span>';
    } else if (consentVal === 'declined') {
        statusBadge = '<span class="cookie-status-badge status-declined">Essential Only Allowed</span>';
    }

    setTimeout(() => {
        const btn = document.getElementById('manage-cookies-trigger-btn');
        if (btn) {
            btn.onclick = (e) => {
                e.preventDefault();
                localStorage.removeItem('cookie_consent');
                showCookieBanner();
            };
        }
    }, 100);

    return `
        <div class="container legal-page-container">
            <h1 class="section-title text-center">Cookie Policy</h1>
            <p class="section-subtitle text-center">Last Updated: August 2026</p>
            
            <div class="legal-content-card">
                <section class="legal-section">
                    <h2>1. What Are Cookies?</h2>
                    <p>Cookies are small text files stored on your computer or mobile device when you visit websites. They help the website remember your actions, preferences, and details over time (such as login states or items added to a cart) so you don't have to re-enter them whenever you return.</p>
                </section>

                <section class="legal-section">
                    <h2>2. How We Use Cookies and Local Storage</h2>
                    <p>At Petals Paradise Events, we respect your privacy and minimize the use of trackers. We do not use third-party marketing trackers or ad cookies. Instead, we use cookies and **local browser storage** to provide core website functionality:</p>
                    <table class="cookies-table">
                        <thead>
                            <tr>
                                <th>Name / Key</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>cart</code></td>
                                <td>Essential LocalStorage</td>
                                <td>Persistent</td>
                                <td>Saves the list of items you've added to your party rental cart.</td>
                            </tr>
                            <tr>
                                <td><code>rental_days</code></td>
                                <td>Essential LocalStorage</td>
                                <td>Persistent</td>
                                <td>Saves your selected rental duration preference (number of days).</td>
                            </tr>
                            <tr>
                                <td><code>fulfillment_method</code></td>
                                <td>Essential LocalStorage</td>
                                <td>Persistent</td>
                                <td>Remembers your preference for order Pickup vs. Delivery.</td>
                            </tr>
                            <tr>
                                <td><code>cookie_consent</code></td>
                                <td>Essential LocalStorage</td>
                                <td>Persistent</td>
                                <td>Saves your preference for cookie consent (Accepted or Declined).</td>
                            </tr>
                            <tr>
                                <td><code>lastPlacedOrderId</code></td>
                                <td>Essential LocalStorage</td>
                                <td>Persistent</td>
                                <td>Temporarily displays your confirmation ID on the order confirmation screen.</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="legal-section">
                    <h2>3. Third-Party Services We Use</h2>
                    <p>We load external libraries from third-party providers who may place cookies or access your browser data for essential services:</p>
                    <ul>
                        <li><strong>Google Maps JavaScript API & Places Autocomplete:</strong> Used to verify delivery addresses at checkout. Google may place cookies or gather browser information to verify referrers and handle authentication.</li>
                        <li><strong>Google Fonts:</strong> Used to load typography. No cookies are set, but your IP address is visible to Google to serve the font files.</li>
                    </ul>
                </section>

                <section class="legal-section">
                    <h2>4. Your Consent Status</h2>
                    <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
                        <p style="margin-bottom: 0.8rem; font-weight: 500;">Current Cookie Preference Settings:</p>
                        <div style="margin-bottom: 1.2rem;">
                            ${statusBadge}
                        </div>
                        <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.2rem;">
                            You can change or reset your cookie preferences at any time. Clicking the button below will clear your previous selection and reopen the cookie consent settings banner.
                        </p>
                        <button id="manage-cookies-trigger-btn" class="btn btn-outline" style="font-size: 0.85rem; padding: 8px 16px;">
                            ⚙️ Reset Cookie Preferences
                        </button>
                    </div>
                </section>

                <section class="legal-section">
                    <h2>5. Managing Cookies in Your Browser</h2>
                    <p>You can block or delete cookies directly through your web browser's settings. Please note that if you block essential local storage/cookies, some features of our site (such as adding items to the rental cart and maintaining a checkout session) will not function correctly.</p>
                </section>
            </div>
            
            <div class="text-center" style="margin-top: 2rem; margin-bottom: 4rem;">
                <a href="#" class="btn btn-outline">Back to Home</a>
            </div>
        </div>
    `;
}

// Cookie Consent Banner Code
function initCookieConsent() {
    const consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        setTimeout(showCookieBanner, 1500);
    }
}

function showCookieBanner() {
    if (document.getElementById('cookie-consent-banner')) return;

    const banner = document.createElement('div');
    banner.id = 'cookie-consent-banner';
    banner.className = 'cookie-consent-banner';
    banner.setAttribute('role', 'alert');
    banner.setAttribute('aria-live', 'assertive');
    
    banner.innerHTML = `
        <div class="cookie-consent-content">
            <div class="cookie-consent-text">
                <h3>🍪 Cookie Consent</h3>
                <p>
                    We use essential cookies and browser local storage to maintain your rental cart items, fulfill checkout details, and enhance your browsing experience. Read our <a href="#privacy" style="color: var(--primary-color); text-decoration: underline;">Privacy Policy</a> to learn more.
                </p>
            </div>
            <div class="cookie-consent-actions">
                <button id="cookie-decline-btn" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; border-color: var(--border-color); color: var(--text-secondary);">Decline</button>
                <button id="cookie-accept-btn" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.85rem;">Accept All</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(banner);
    
    document.getElementById('cookie-accept-btn').onclick = () => {
        localStorage.setItem('cookie_consent', 'accepted');
        hideCookieBanner();
    };
    
    document.getElementById('cookie-decline-btn').onclick = () => {
        localStorage.setItem('cookie_consent', 'declined');
        hideCookieBanner();
    };
}

function hideCookieBanner() {
    const banner = document.getElementById('cookie-consent-banner');
    if (banner) {
        banner.classList.add('hide');
        setTimeout(() => {
            if (banner.parentNode) {
                banner.parentNode.removeChild(banner);
            }
            if (window.location.hash === '#cookies') {
                router(true);
            }
        }, 400);
    }
}

// Router
function router(preserveScroll = false) {
    const hash = window.location.hash || '#';
    const main = document.getElementById('main-content');

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === hash || (hash === '#' && link.getAttribute('href') === '#')) {
            link.classList.add('active');
        }
    });

    let content = '';
    let pageTitle = 'Petals Paradise Events | Event Decor & Party Rentals DMV';
    let metaDesc = 'Transform your celebrations into unforgettable moments with Petals Paradise Events. Premium party rentals, elegant backdrops, and custom decor in the DMV area.';

    switch (hash) {
        case '#': 
            content = renderHome(); 
            break;
        case '#rentals': 
            content = renderRentals(); 
            pageTitle = 'Party & Event Rentals | Petals Paradise Events';
            metaDesc = 'Explore our wide range of event rentals including rectangular folding tables, gold chairs, tents, and traditional backdrops.';
            break;
        case '#graduation': 
            content = renderGraduation(); 
            pageTitle = 'Graduation Party Decor 2026 | Petals Paradise Events';
            metaDesc = 'Make your 2026 graduation unforgettable with marquee letters, custom setups, and party essentials.';
            break;
        case '#services': 
            content = renderServices(); 
            pageTitle = 'Our Event Services | Petals Paradise Events';
            metaDesc = 'From weddings to housewarmings, we offer comprehensive decor solutions tailored to your unique celebration.';
            break;
        case '#gallery': 
            content = renderGallery(); 
            pageTitle = 'Event Inspiration Gallery | Petals Paradise Events';
            metaDesc = 'View our portfolio of beautiful event setups, traditional Seemantham backdrops, and graduation celebrations.';
            break;
        case '#videos': 
            content = renderVideos(); 
            pageTitle = 'Video Highlights | Petals Paradise Events';
            metaDesc = 'Watch our event decor in action through cinematic video highlights of our latest setups.';
            break;
        case '#contact': 
            content = renderContact(); 
            pageTitle = 'Contact Us | Petals Paradise Events';
            metaDesc = 'Get a custom quote for your next event. Contact Petals Paradise Events for premium decor and rentals in DMV.';
            break;
        case '#cart': 
            content = renderCart(); 
            pageTitle = 'Your Rental Cart | Petals Paradise Events';
            break;
        case '#checkout': 
            content = renderCheckout(); 
            pageTitle = 'Secure Checkout | Petals Paradise Events';
            break;
        case '#privacy':
            content = renderPrivacy();
            pageTitle = 'Privacy Policy | Petals Paradise Events';
            metaDesc = 'Read our Privacy Policy to understand how Petals Paradise Events handles your personal data, rental orders, and cookie preferences.';
            break;
        case '#terms':
            content = renderTerms();
            pageTitle = 'Terms of Service | Petals Paradise Events';
            metaDesc = 'Review the Terms of Service and Rental Agreement details for booking with Petals Paradise Events.';
            break;
        case '#cookies':
            content = renderCookies();
            pageTitle = 'Cookie Policy | Petals Paradise Events';
            metaDesc = 'Learn about our cookie usage and manage your consent preferences for Petals Paradise Events website.';
            break;
        case '#confirmation':
            content = renderConfirmation();
            pageTitle = 'Order Confirmed | Petals Paradise Events';
            break;
        default: 
            content = renderHome();
    }

    document.title = pageTitle;
    const metaDescriptionTag = document.querySelector('meta[name="description"]');
    if (metaDescriptionTag) {
        metaDescriptionTag.setAttribute('content', metaDesc);
    }

    main.innerHTML = content;
    feather.replace(); // Re-initialize icons

    // Reset Sticky Mobile Actions
    const stickyContainer = document.getElementById('sticky-action-container');
    if (stickyContainer) stickyContainer.classList.remove('visible');

    // Initialize Sticky Observers based on page
    if (hash === '#cart') {
        setTimeout(() => initStickyObserver('cart-checkout-btn', 'Proceed to Checkout', "goToCheckout(event)"), 200);
    } else if (hash === '#checkout') {
        setTimeout(() => initStickyObserver('checkout-submit-btn', 'Submit Rental Request', "document.getElementById('checkout-submit-btn').click()"), 200);
        if (typeof window.loadGoogleMaps === 'function') window.loadGoogleMaps();
        setTimeout(window.initAutocomplete, 250);
    }


    if (!preserveScroll) {
        window.scrollTo(0, 0); // Scroll to top on page change
    }

}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    renderBanner();
    renderNavbar();
    renderFooter();
    router();
    window.addEventListener('hashchange', router);
    renderSMSWidget();
    initImageModal();
    initAIChatbot();
    initCookieConsent();
});

function initImageModal() {
    const modal = document.getElementById('image-modal');
    const modalImg = document.getElementById('modal-img');
    const closeBtn = document.querySelector('.modal-close');

    if (!modal || !modalImg) return;

    // Track the element that opened the modal for focus restoration
    let lastFocused = null;

    const openModal = (src, altText) => {
        lastFocused = document.activeElement;
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        modalImg.src = src;
        modalImg.alt = altText || 'Rental item photo';
        document.body.style.overflow = 'hidden';
        // Move focus to close button for keyboard users
        if (closeBtn) setTimeout(() => closeBtn.focus(), 50);
    };

    // Close modal
    const closeModal = () => {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // Restore focus
        if (lastFocused) lastFocused.focus();
    };

    // Global listener for image clicks
    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'IMG' && !e.target.classList.contains('no-zoom') && !e.target.closest('.sms-btn')) {
            openModal(e.target.src, e.target.alt);
        }
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
    });

    // Expose so card inline-onclick can call it
    window.openImageModal = openModal;
}

function renderSMSWidget() {
    if (document.getElementById('sms-widget')) return;
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const bodySeparator = isIOS ? '&' : '?';
    const bodyText = encodeURIComponent("Hi Petals Paradise Events! I'm interested in your event decor rentals for an upcoming event. Could you please help me?");
    const smsHref = `sms:+18484486993${bodySeparator}body=${bodyText}`;

    const widget = document.createElement('div');
    widget.id = 'sms-widget';
    widget.innerHTML = `
        <a href="${smsHref}" class="sms-btn" title="Text us">
            <div class="sms-pulsate"></div>
            <i data-feather="message-square" style="width: 28px; height: 28px;"></i>
            <span class="sms-text">Text Petals Paradise Events</span>
        </a>
    `;
    document.body.appendChild(widget);
    if (window.feather) feather.replace();
}

/**
 * AI Chatbot integration
 */
function initAIChatbot() {
    const chatWidget = document.getElementById('ai-chat-widget');
    const chatTrigger = document.getElementById('ai-chat-trigger');
    const chatWindow = document.getElementById('ai-chat-window');
    const chatClose = document.getElementById('ai-chat-close');
    const chatMessages = document.getElementById('ai-chat-messages');
    const chatForm = document.getElementById('ai-chat-input-form');
    const chatInput = document.getElementById('ai-chat-input');
    
    if (!chatWidget || !chatTrigger || !chatWindow || !chatMessages || !chatForm || !chatInput) return;

    let chatHistory = [];
    let isOpen = false;

    // Toggle chatbot window
    const toggleChat = (forceState) => {
        isOpen = forceState !== undefined ? forceState : !isOpen;
        const smsWidget = document.getElementById('sms-widget');
        if (isOpen) {
            if (smsWidget) smsWidget.style.display = 'none';
            chatWindow.style.display = 'flex';
            setTimeout(() => {
                chatWindow.classList.add('active');
                chatWindow.setAttribute('aria-hidden', 'false');
                chatInput.focus();
            }, 10);
            chatTrigger.style.display = 'none';
        } else {
            chatWindow.classList.remove('active');
            chatWindow.setAttribute('aria-hidden', 'true');
            setTimeout(() => {
                chatWindow.style.display = 'none';
                chatTrigger.style.display = 'flex';
                if (smsWidget) smsWidget.style.display = 'block';
            }, 300);
        }
    };

    chatTrigger.addEventListener('click', () => toggleChat(true));
    chatClose.addEventListener('click', () => toggleChat(false));

    // Handle suggestion chips
    const chips = document.querySelectorAll('.chat-suggestion-chip');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            const query = chip.getAttribute('data-query');
            if (query) {
                chatInput.value = query;
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    });

    // Helper: format markdown text
    const formatResponseText = (text) => {
        // Escape HTML
        let formatted = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Format bold markdown (**text**)
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Format bullet points
        formatted = formatted.replace(/(?:^|\n)[-*]\s+(.+)/g, '<br>• $1');
        
        // Clean linebreaks
        formatted = formatted.replace(/\n/g, '<br>');
        
        return formatted;
    };

    // Helper: append message element to chat
    const appendMessage = (sender, htmlContent) => {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${sender === 'user' ? 'user-msg' : 'system-msg'}`;
        msgDiv.innerHTML = `<div class="message-bubble">${htmlContent}</div>`;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return msgDiv;
    };

    // Helper: parse [PLACE_ORDER: {json}] tag and execute automated checkout
    const parseOrderTag = async (text, messageBubbleContainer) => {
        const orderTagRegex = /\[PLACE_ORDER:(.*?)\]/s;
        const match = orderTagRegex.exec(text);
        
        if (!match) return;
        
        const rawJsonStr = match[1].trim();
        let orderData = null;
        try {
            orderData = JSON.parse(rawJsonStr);
        } catch (e) {
            console.error('Failed to parse PLACE_ORDER JSON tag:', e);
            return;
        }

        // Clean [PLACE_ORDER:...] from the text response
        const cleanedText = text.replace(orderTagRegex, '').trim();
        if (messageBubbleContainer && messageBubbleContainer.querySelector('.message-bubble')) {
            messageBubbleContainer.querySelector('.message-bubble').innerHTML = formatResponseText(cleanedText);
        }

        // Prepare order placement loader card
        const cardContainer = document.createElement('div');
        cardContainer.className = 'chat-order-card chat-order-loading';
        cardContainer.innerHTML = `
            <div class="chat-order-header">
                <span class="chat-order-badge">⏳ Submitting Your Order...</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">Connecting to Petals Paradise Events reservation engine...</p>
        `;
        if (messageBubbleContainer) {
            messageBubbleContainer.appendChild(cardContainer);
        }

        const isMobileApp = window.location.origin.includes('localhost') || 
                            window.location.protocol.startsWith('file:') || 
                            window.location.hostname === '';
        const orderEndpoint = isMobileApp ? 'https://www.petalsparadiseevents.com/api/place_order.php' : '/api/place_order.php';

        try {
            const res = await fetch(orderEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });
            const result = await res.json();

            if (result.success) {
                const orderId = result.order_id || 'PPE-CONFIRMED';
                const totalVal = parseFloat(result.total || orderData.total || 0).toFixed(2);

                cardContainer.className = 'chat-order-card chat-order-success';
                cardContainer.innerHTML = `
                    <div class="chat-order-header">
                        <span class="chat-order-badge success">✨ Order Placed Successfully!</span>
                        <span class="chat-order-id">${orderId}</span>
                    </div>
                    <div class="chat-order-body">
                        <div class="chat-order-row"><span>👤 Customer:</span> <strong>${escapeHtml(orderData.name)}</strong></div>
                        <div class="chat-order-row"><span>🗓️ Event Date:</span> <strong>${escapeHtml(orderData.event_date)}</strong></div>
                        <div class="chat-order-row"><span>🚚 Fulfillment:</span> <strong>${escapeHtml(orderData.fulfillment_method)}</strong></div>
                        ${orderData.delivery_address ? `<div class="chat-order-row"><span>📍 Address:</span> <span>${escapeHtml(orderData.delivery_address)}</span></div>` : ''}
                        <div class="chat-order-row total"><span>💵 Total Estimate:</span> <strong>$${totalVal}</strong></div>
                    </div>
                    <div class="chat-order-status-pill">
                        ● Status: <strong>Pending Confirmation</strong> (Reviewing Inventory)
                    </div>
                    <div class="chat-order-footer">
                        📧 Confirmation alert sent to <strong>${escapeHtml(orderData.email)}</strong>. We will contact you at <strong>${escapeHtml(orderData.phone)}</strong> shortly!
                    </div>
                `;

                if (typeof window.clearCart === 'function') {
                    window.clearCart();
                }
            } else {
                cardContainer.className = 'chat-order-card chat-order-error';
                cardContainer.innerHTML = `
                    <div class="chat-order-header">
                        <span class="chat-order-badge error">❌ Order Submission Failed</span>
                    </div>
                    <p style="font-size: 0.85rem; color: #ef4444; margin-top: 0.5rem;">${escapeHtml(result.error || 'Unable to place order automatically.')}</p>
                    <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">Please call us at +1 848-448-6993 or use our inquiry cart.</p>
                `;
            }
        } catch (err) {
            console.error('Order submission error:', err);
            cardContainer.className = 'chat-order-card chat-order-error';
            cardContainer.innerHTML = `
                <div class="chat-order-header">
                    <span class="chat-order-badge error">❌ Network Connection Error</span>
                </div>
                <p style="font-size: 0.85rem; color: #ef4444; margin-top: 0.5rem;">Could not connect to order server.</p>
            `;
        }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    // Helper: parse [ADD_TO_CART:id] brackets and render item recommendation cards
    const parseRecommendations = (text, messageBubbleContainer) => {
        // First check for order tag
        if (text.includes('[PLACE_ORDER:')) {
            parseOrderTag(text, messageBubbleContainer);
            return;
        }

        const regex = /\[ADD_TO_CART:(\d+)\]/g;
        let match;
        const itemIds = [];
        
        while ((match = regex.exec(text)) !== null) {
            itemIds.push(parseInt(match[1], 10));
        }

        // Clean brackets from text message
        const cleanedText = text.replace(regex, '').trim();
        if (messageBubbleContainer && messageBubbleContainer.querySelector('.message-bubble')) {
            messageBubbleContainer.querySelector('.message-bubble').innerHTML = formatResponseText(cleanedText);
        }

        // Append interactive recommendation cards for matching catalog items
        itemIds.forEach(id => {
            const item = rentalItems.find(i => i.id === id);
            if (item) {
                const card = document.createElement('div');
                card.className = 'chat-recommendation-card';
                // Adjust images to live production if running local web view environment
                const imgUrl = item.img.startsWith('./') ? `https://www.petalsparadiseevents.com/${item.img.substring(2)}` : item.img;
                
                card.innerHTML = `
                    <img src="${imgUrl}" class="chat-recommendation-img no-zoom" alt="${item.title}">
                    <div class="chat-recommendation-info">
                        <div class="chat-recommendation-title">${item.title}</div>
                        <div class="chat-recommendation-price">$${item.price} / day</div>
                        <button class="chat-recommendation-btn" onclick="window.handleAddToCart(${item.id})">Add to Cart</button>
                    </div>
                `;
                messageBubbleContainer.appendChild(card);
            }
        });

        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    // Submit form handler
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const query = chatInput.value.trim();
        if (!query) return;

        chatInput.value = '';
        appendMessage('user', query);

        // Add loading indicator bubble
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message system-msg';
        loadingDiv.innerHTML = `
            <div class="message-bubble">
                <div class="chat-loading-bubble">
                    <span class="loading-dot"></span>
                    <span class="loading-dot"></span>
                    <span class="loading-dot"></span>
                </div>
            </div>
        `;
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // API Endpoint routing: Redirect to production if inside the Capacitor/localhost app wrapper
        const isMobileApp = window.location.origin.includes('localhost') || 
                            window.location.protocol.startsWith('file:') || 
                            window.location.hostname === '';
        const endpoint = isMobileApp ? 'https://www.petalsparadiseevents.com/api/chat.php' : '/api/chat.php';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: json_encode_mock({ message: query, history: chatHistory })
            });

            const data = await response.json();
            
            // Remove loading bubble
            chatMessages.removeChild(loadingDiv);

            if (data.error && !data.response) {
                const debugMsg = data.debug ? ` (${data.debug})` : '';
                appendMessage('system', `🌸 Sorry, I am having trouble connecting to my servers right now${debugMsg}. Please try again later.`);
            } else {
                const reply = data.response;
                
                // Save conversation history (limit to last 12 messages to optimize performance)
                chatHistory.push({ role: 'user', text: query });
                chatHistory.push({ role: 'model', text: reply });
                if (chatHistory.length > 12) {
                    chatHistory.shift();
                    chatHistory.shift();
                }

                // Render assistant bubble and parse any item cart integration brackets
                const botBubble = appendMessage('system', '');
                parseRecommendations(reply, botBubble);
            }
        } catch (err) {
            console.error('Chat API Error:', err);
            chatMessages.removeChild(loadingDiv);
            const errDetail = err && err.message ? err.message : '';
            appendMessage('system', `🌸 Connection issue: ${errDetail || 'Unable to reach chatbot API'}. Please try again or call us at 848-448-6993.`);
        }
    });

    // Helper: simple JSON stringify because of environment dependencies
    function json_encode_mock(obj) {
        return JSON.stringify(obj);
    }
}

