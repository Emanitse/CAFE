document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('reports-table-body');
    if (!tableBody) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const searchInput = document.getElementById('reportSearch');
    const categorySelect = document.getElementById('reportCategoryFilter');
    const itemSelect = document.getElementById('reportItemFilter');
    const fromDateInput = document.getElementById('reportFromDate');
    const toDateInput = document.getElementById('reportToDate');
    const resetBtn = document.getElementById('resetReportFilters');

    const summaryCount = document.getElementById('summary-count');
    const summaryQty = document.getElementById('summary-qty');
    const summarySales = document.getElementById('summary-sales');

    // Export Selection Mode Elements
    const toggleExportModeBtn = document.getElementById('toggleExportModeBtn');
    const cancelExportModeBtn = document.getElementById('cancelExportModeBtn');
    const exportSelectionBar = document.getElementById('exportSelectionBar');
    const exportBottomBar = document.getElementById('exportBottomBar');
    const selectAllFilteredCheckbox = document.getElementById('selectAllFilteredCheckbox');
    const selectCurrentPageCheckbox = document.getElementById('selectCurrentPageCheckbox');
    const totalFilteredSelectableCount = document.getElementById('totalFilteredSelectableCount');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const selectedRowBadge = document.getElementById('selectedRowBadge');
    const exportPdfBtn = document.getElementById('exportPdfBtn');

    const PER_PAGE = 10;
    let allLogs = [];
    let currentPage = 1;
    let searchTimeout = null;

    // Selection State (Persists across pagination pages)
    let isExportMode = false;
    const selectedIds = new Set();

    // Sync checkboxes, counters, and Export PDF button state
    function syncSelectionUI() {
        const totalFiltered = allLogs.length;
        const selectedCount = selectedIds.size;

        totalFilteredSelectableCount.textContent = totalFiltered;
        selectedRowBadge.textContent = `${selectedCount} selected`;
        exportPdfBtn.textContent = `Export PDF (${selectedCount})`;
        exportPdfBtn.disabled = selectedCount === 0;

        // Global "Select All across all pages" checkbox state
        if (totalFiltered > 0 && selectedCount === totalFiltered) {
            selectAllFilteredCheckbox.checked = true;
            selectAllFilteredCheckbox.indeterminate = false;
        } else if (selectedCount > 0) {
            selectAllFilteredCheckbox.checked = false;
            selectAllFilteredCheckbox.indeterminate = true;
        } else {
            selectAllFilteredCheckbox.checked = false;
            selectAllFilteredCheckbox.indeterminate = false;
        }

        // Current page header checkbox state
        const pageCheckboxes = Array.from(tableBody.querySelectorAll('.row-select-checkbox'));
        if (pageCheckboxes.length > 0) {
            const checkedOnPage = pageCheckboxes.filter(cb => cb.checked).length;
            if (checkedOnPage === pageCheckboxes.length) {
                selectCurrentPageCheckbox.checked = true;
                selectCurrentPageCheckbox.indeterminate = false;
            } else if (checkedOnPage > 0) {
                selectCurrentPageCheckbox.checked = false;
                selectCurrentPageCheckbox.indeterminate = true;
            } else {
                selectCurrentPageCheckbox.checked = false;
                selectCurrentPageCheckbox.indeterminate = false;
            }
        } else {
            selectCurrentPageCheckbox.checked = false;
            selectCurrentPageCheckbox.indeterminate = false;
        }
    }

    // Toggle Selection Mode On / Off
    function setExportMode(active) {
        isExportMode = active;

        if (isExportMode) {
            toggleExportModeBtn.textContent = 'Exit Selection';
            toggleExportModeBtn.classList.remove('bg-coffee', 'text-white');
            toggleExportModeBtn.classList.add('btn-outline-dark');
            exportSelectionBar.classList.remove('d-none');
            exportBottomBar.classList.remove('d-none');
            document.querySelectorAll('.select-col').forEach(el => el.classList.remove('d-none'));
        } else {
            selectedIds.clear();
            toggleExportModeBtn.textContent = 'Select to Export';
            toggleExportModeBtn.classList.remove('btn-outline-dark');
            toggleExportModeBtn.classList.add('bg-coffee', 'text-white');
            exportSelectionBar.classList.add('d-none');
            exportBottomBar.classList.add('d-none');
            document.querySelectorAll('.select-col').forEach(el => el.classList.add('d-none'));
        }

        renderReportPage();
    }

    toggleExportModeBtn.addEventListener('click', () => setExportMode(!isExportMode));
    cancelExportModeBtn.addEventListener('click', () => setExportMode(false));

    // Select / Deselect ALL filtered rows across ALL pagination pages
    selectAllFilteredCheckbox.addEventListener('change', function () {
        if (this.checked) {
            allLogs.forEach(row => selectedIds.add(Number(row.orderdetailID)));
        } else {
            selectedIds.clear();
        }
        renderReportPage();
    });

    // Select / Deselect all rows on the CURRENT page only
    selectCurrentPageCheckbox.addEventListener('change', function () {
        const pageCheckboxes = tableBody.querySelectorAll('.row-select-checkbox');
        pageCheckboxes.forEach(cb => {
            const id = Number(cb.dataset.id);
            cb.checked = this.checked;
            if (this.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        syncSelectionUI();
    });

    // Clear all selections
    clearSelectionBtn.addEventListener('click', function () {
        selectedIds.clear();
        renderReportPage();
    });

    // Individual row checkbox change (Event Delegation)
    tableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-select-checkbox')) {
            const id = Number(e.target.dataset.id);
            if (e.target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
            syncSelectionUI();
        }
    });

    // Trigger PDF Download via hidden POST form (no page reload)
    exportPdfBtn.addEventListener('click', function () {
        if (selectedIds.size === 0) return;

        const catText = categorySelect.options[categorySelect.selectedIndex]?.text || 'All Categories';
        const itemText = itemSelect.options[itemSelect.selectedIndex]?.text || 'All Items';
        const fromVal = fromDateInput.value;
        const toVal = toDateInput.value;

        let dateRangeLabel = 'All Dates';
        if (fromVal && toVal) dateRangeLabel = `${fromVal} to ${toVal}`;
        else if (fromVal) dateRangeLabel = `From ${fromVal}`;
        else if (toVal) dateRangeLabel = `Until ${toVal}`;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/reports/export-pdf';
        form.style.display = 'none';

        const fields = {
            _token: csrfToken,
            selected_ids: JSON.stringify(Array.from(selectedIds)),
            date_range_label: dateRangeLabel,
            filter_label: `${catText} / ${itemText}`,
        };

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
    });

    // 1. Load active categories into Category combobox
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

    // 2. Load active menu items into Item combobox (cascaded by categoryID)
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

    // 3. Render current page slice (10 per page)
    function renderReportPage() {
        const totalPages = Math.max(1, Math.ceil(allLogs.length / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        window.renderTablePagination({
            containerId: 'reports-pagination',
            totalItems: allLogs.length,
            currentPage,
            perPage: PER_PAGE,
            onPageChange: (newPage) => {
                currentPage = newPage;
                renderReportPage();
            }
        });

        const colSpan = isExportMode ? 9 : 8;

        if (allLogs.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="text-center text-muted py-5">
                        No matching transaction records found.
                    </td>
                </tr>
            `;
            syncSelectionUI();
            return;
        }

        const startIndex = (currentPage - 1) * PER_PAGE;
        const pageLogs = allLogs.slice(startIndex, startIndex + PER_PAGE);
        const selectColClass = isExportMode ? '' : 'd-none';

        tableBody.innerHTML = pageLogs.map(row => {
            const id = Number(row.orderdetailID);
            const isChecked = selectedIds.has(id) ? 'checked' : '';
            const inactiveBadge = row.item_active === 0
                ? `<span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Archived</span>`
                : '';

            return `
                <tr>
                    <td class="px-3 text-center select-col ${selectColClass}">
                        <input class="form-check-input row-select-checkbox" type="checkbox" data-id="${id}" ${isChecked} style="cursor: pointer;">
                    </td>
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

        syncSelectionUI();
    }

    // 4. Fetch filtered audit trail from backend
    async function fetchOrderHistory(resetPage = true) {
        if (resetPage) currentPage = 1;

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

                allLogs = data.logs;

                // Keep only selections that still belong to the active filtered dataset
                const validIds = new Set(allLogs.map(r => Number(r.orderdetailID)));
                selectedIds.forEach(id => {
                    if (!validIds.has(id)) selectedIds.delete(id);
                });

                renderReportPage();
            }
        } catch (err) {
            const colSpan = isExportMode ? 9 : 8;
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="text-center text-danger py-4">
                        Error loading report data.
                    </td>
                </tr>
            `;
        }
    }

    // Filter Event Listeners
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchOrderHistory(true), 300);
    });

    categorySelect.addEventListener('change', async function () {
        await loadItems(this.value);
        fetchOrderHistory(true);
    });

    itemSelect.addEventListener('change', () => fetchOrderHistory(true));
    fromDateInput.addEventListener('change', () => fetchOrderHistory(true));
    toDateInput.addEventListener('change', () => fetchOrderHistory(true));

    resetBtn.addEventListener('click', async function () {
        searchInput.value = '';
        fromDateInput.value = '';
        toDateInput.value = '';
        categorySelect.value = 'all';
        await loadItems('all');
        itemSelect.value = 'all';
        fetchOrderHistory(true);
    });

    document.addEventListener('visibilitychange', async function () {
        if (document.visibilityState === 'visible') {
            await loadCategories();
            await loadItems(categorySelect.value);
            fetchOrderHistory(false);
        }
    });

    (async function init() {
        await loadCategories();
        await loadItems('all');
        await fetchOrderHistory(true);
    })();
});