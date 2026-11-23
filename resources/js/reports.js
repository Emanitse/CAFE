document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('reports-table-body');
    if (!tableBody) return;

    const searchInput = document.getElementById('reportSearch');
    const categorySelect = document.getElementById('reportCategoryFilter');
    const itemSelect = document.getElementById('reportItemFilter');
    const fromDateInput = document.getElementById('reportFromDate');
    const toDateInput = document.getElementById('reportToDate');
    const resetBtn = document.getElementById('resetReportFilters');

    const summaryCount = document.getElementById('summary-count');
    const summaryQty = document.getElementById('summary-qty');
    const summarySales = document.getElementById('summary-sales');

    let searchTimeout = null;


    async function loadCategories() {
        const previousValue = categorySelect.value || 'all';

        try {
            const res = await fetch('/reports/categories', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                categorySelect.innerHTML = '<option value="all">All Categories</option>';
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.categoryID;
                    option.textContent = cat.categoryname;
                    categorySelect.appendChild(option);
                });

           
                const exists = [...categorySelect.options].some(opt => opt.value == previousValue);
                categorySelect.value = exists ? previousValue : 'all';
            }
        } catch (err) {
            console.error('Failed to load categories:', err);
        }
    }

    async function loadItems(categoryID = 'all') {
        const previousValue = itemSelect.value || 'all';

        try {
            const params = new URLSearchParams({ categoryID });
            const res = await fetch(`/reports/items?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                itemSelect.innerHTML = '<option value="all">All Items</option>';
                data.items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.menuID;
                    option.textContent = item.itemname;
                    itemSelect.appendChild(option);
                });

               
                const exists = [...itemSelect.options].some(opt => opt.value == previousValue);
                itemSelect.value = exists ? previousValue : 'all';
            }
        } catch (err) {
            console.error('Failed to load items:', err);
        }
    }

  
    async function fetchOrderHistory() {
        const params = new URLSearchParams({
            search: searchInput.value.trim(),
            categoryID: categorySelect.value,
            menuID: itemSelect.value,
            fromDate: fromDateInput.value,
            toDate: toDateInput.value,
        });

        try {
            const res = await fetch(`/reports/history?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
            
                summaryCount.textContent = data.summary.count;
                summaryQty.textContent = data.summary.totalQuantity;
                summarySales.textContent = `₱${data.summary.totalSales}`;

                if (data.logs.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No matching transaction records found.
                            </td>
                        </tr>
                    `;
                    return;
                }

                tableBody.innerHTML = data.logs.map(row => {
                    const inactiveBadge = row.item_active === 0
                        ? `<span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Archived</span>`
                        : '';

                    return `
                        <tr>
                            <td class="px-4 text-muted small text-nowrap">${row.formatted_date}</td>
                            <td class="px-4 fw-bold text-secondary">#${String(row.orderID).padStart(4, '0')}</td>
                            <td class="px-4 fw-bold text-dark">${row.itemname ?? 'Unknown Item'}${inactiveBadge}</td>
                            <td class="px-4 text-muted">${row.categoryname ?? 'Uncategorized'}</td>
                            <td class="px-4 text-center fw-bold">${row.quantity}</td>
                            <td class="px-4 text-end fw-bold text-success">₱${parseFloat(row.subtotal).toFixed(2)}</td>
                            <td class="px-4">
                                <span class="badge bg-light text-dark border">${row.paymentmethod ?? 'N/A'}</span>
                            </td>
                            <td class="px-4 text-muted">${row.cashier ?? 'System'}</td>
                        </tr>
                    `;
                }).join('');
            }
        } catch (err) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        Error loading report data.
                    </td>
                </tr>
            `;
        }
    }

    
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchOrderHistory();
        }, 300);
    });

    categorySelect.addEventListener('change', async function () {
        await loadItems(this.value);
        fetchOrderHistory();
    });

    itemSelect.addEventListener('change', function () {
        fetchOrderHistory();
    });

    fromDateInput.addEventListener('change', function () {
        fetchOrderHistory();
    });

    toDateInput.addEventListener('change', function () {
        fetchOrderHistory();
    });

  
    resetBtn.addEventListener('click', async function () {
        searchInput.value = '';
        fromDateInput.value = '';
        toDateInput.value = '';
        categorySelect.value = 'all';
        await loadItems('all');
        itemSelect.value = 'all';
        fetchOrderHistory();
    });

   
    document.addEventListener('visibilitychange', async function () {
        if (document.visibilityState === 'visible') {
            await loadCategories();
            await loadItems(categorySelect.value);
            fetchOrderHistory();
        }
    });

    // Initial Boot
    (async function init() {
        await loadCategories();
        await loadItems('all');
        await fetchOrderHistory();
    })();
});