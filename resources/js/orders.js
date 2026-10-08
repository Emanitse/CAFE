document.addEventListener('DOMContentLoaded', function() {
    // Only execute if we are on the Orders page
    if (!document.getElementById('category-filters')) return;

    let cart = [];

    // 1. Category Filter Logic
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => {
                b.classList.remove('btn-dark', 'active');
                b.classList.add('btn-outline-dark');
            });
            this.classList.remove('btn-outline-dark');
            this.classList.add('btn-dark', 'active');

            const selectedCategory = this.dataset.category;
            document.querySelectorAll('.menu-card-wrapper').forEach(card => {
                if (selectedCategory === 'all' || card.dataset.category === selectedCategory) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 2. Cart Logic
    document.querySelectorAll('.item-card').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            
            const existing = cart.find(item => item.menuID == id);
            if (existing) {
                existing.quantity++;
                existing.subtotal = existing.quantity * price;
            } else {
                cart.push({ menuID: id, name, price, quantity: 1, subtotal: price });
            }
            renderCart();
        });
    });

    // Event delegation for cart buttons
    document.getElementById('cart-table-body').addEventListener('click', function(e) {
        const id = e.target.dataset.id;
        if (!id) return;

        const item = cart.find(i => i.menuID == id);
        if (!item) return;

        if (e.target.classList.contains('btn-plus')) {
            item.quantity++;
            item.subtotal = item.quantity * item.price;
            renderCart();
        } else if (e.target.classList.contains('btn-minus')) {
            item.quantity--;
            if (item.quantity === 0) {
                cart = cart.filter(i => i.menuID != id);
            } else {
                item.subtotal = item.quantity * item.price;
            }
            renderCart();
        } else if (e.target.classList.contains('btn-remove')) {
            cart = cart.filter(i => i.menuID != id);
            renderCart();
        }
    });

    function renderCart() {
        const tbody = document.getElementById('cart-table-body');
        const checkoutBtn = document.getElementById('checkout-btn');
        const totalDisplay = document.getElementById('total-amount-display');
        
        tbody.innerHTML = '';
        let grandTotal = 0;

        checkoutBtn.disabled = cart.length === 0;

        cart.forEach(item => {
            grandTotal += item.subtotal;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="fw-bold w-50">${item.name}</td>
                <td class="text-center w-25 text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary btn-minus px-2" data-id="${item.menuID}">-</button>
                    <span class="mx-1 fw-bold">${item.quantity}</span>
                    <button class="btn btn-sm btn-outline-secondary btn-plus px-2" data-id="${item.menuID}">+</button>
                </td>
                <td class="text-end w-25">₱${item.subtotal.toFixed(2)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-danger btn-remove" data-id="${item.menuID}">X</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        totalDisplay.textContent = `₱${grandTotal.toFixed(2)}`;
        totalDisplay.dataset.rawTotal = grandTotal;
    }

    // 3. Checkout
    document.getElementById('checkout-btn').addEventListener('click', async function() {
        const totalAmount = document.getElementById('total-amount-display').dataset.rawTotal;
        this.disabled = true;
        this.textContent = 'PROCESSING...';

        try {
            const response = await fetch('/orders/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ cart, totalAmount })
            });

            const data = await response.json();
            if (data.success) {
                window.location.href = `/payments/${data.orderID}`;
            } else {
                alert(data.message || 'Checkout failed');
                this.disabled = false;
                this.textContent = 'CHECKOUT';
            }
        } catch (err) {
            alert('Something went wrong');
            this.disabled = false;
            this.textContent = 'CHECKOUT';
        }
    });
});