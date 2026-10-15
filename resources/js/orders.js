document.addEventListener('DOMContentLoaded', function() {
    // Only execute if we are on the Orders page
    if (!document.getElementById('category-filters')) return;

    let cart = [];

    // 1. Category Filter Logic
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => {
                b.classList.remove('active');
                
            });
           
            this.classList.add('active');

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

    // 3. Checkout & Payment Modal Logic
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const amountReceivedInput = document.getElementById('amountReceived');
    const changeAmountInput = document.getElementById('changeAmount');
    const completeBtn = document.getElementById('complete-payment-btn');

    // Open Modal when Checkout is clicked
    document.getElementById('checkout-btn').addEventListener('click', function() {
        const totalAmount = document.getElementById('total-amount-display').dataset.rawTotal;
        document.getElementById('modal-total-display').textContent = `₱${parseFloat(totalAmount).toFixed(2)}`;
        
        // Reset modal fields
        amountReceivedInput.value = '';
        changeAmountInput.value = '0.00';
        completeBtn.disabled = true;

        paymentModal.show();
    });

    // Calculate Change Dynamically
    amountReceivedInput.addEventListener('input', function() {
        const totalAmount = parseFloat(document.getElementById('total-amount-display').dataset.rawTotal);
        const received = parseFloat(this.value) || 0;
        const change = received - totalAmount;

        if (change >= 0) {
            changeAmountInput.value = change.toFixed(2);
            changeAmountInput.classList.remove('text-danger');
            changeAmountInput.classList.add('text-success');
            completeBtn.disabled = false;
        } else {
            changeAmountInput.value = 'Insufficient';
            changeAmountInput.classList.remove('text-success');
            changeAmountInput.classList.add('text-danger');
            completeBtn.disabled = true;
        }
    });

    // Handle Final Submission
    completeBtn.addEventListener('click', async function() {
        const totalAmount = document.getElementById('total-amount-display').dataset.rawTotal;
        const amountReceived = amountReceivedInput.value;
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        
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
                body: JSON.stringify({ cart, totalAmount, paymentMethod, amountReceived })
            });

            const data = await response.json();
            if (data.success) {
                paymentModal.hide();
                alert(`Payment Complete! Order ID: ${data.orderID}`);
                
                // Clear the cart and reset UI for the next customer
                cart = [];
                renderCart();
                this.textContent = 'COMPLETE PAYMENT';
            } else {
                alert(data.message || 'Checkout failed');
                this.disabled = false;
                this.textContent = 'COMPLETE PAYMENT';
            }
        } catch (err) {
            alert('Something went wrong during payment.');
            this.disabled = false;
            this.textContent = 'COMPLETE PAYMENT';
        }
    });
});