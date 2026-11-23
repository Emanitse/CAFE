document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('menu-table-body');
    if (!tableBody) return;

    const addModal = new bootstrap.Modal(document.getElementById('addMenuModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteMenuModal'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('openAddMenuBtn')?.addEventListener('click', () => {
        addModal.show();
    });
    
    const PER_PAGE = 10;
    let currentCategory = 'all';
    let currentPage = 1;

    function applyMenuPagination() {
        const allRows = Array.from(tableBody.querySelectorAll('.menu-row'));
        const matchingRows = allRows.filter(row =>
            currentCategory === 'all' || row.dataset.category === currentCategory
        );

        const totalPages = Math.max(1, Math.ceil(matchingRows.length / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * PER_PAGE;
        const endIndex = startIndex + PER_PAGE;
        const visibleSet = new Set(matchingRows.slice(startIndex, endIndex));

        allRows.forEach(row => {
            row.style.display = visibleSet.has(row) ? '' : 'none';
        });

        // Empty state handling
        let emptyRow = document.getElementById('menu-empty-row');
        if (matchingRows.length === 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'menu-empty-row';
                emptyRow.innerHTML = `<td colspan="5" class="text-center text-muted py-5">No menu items found.</td>`;
                tableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }

        window.renderTablePagination({
            containerId: 'menu-pagination',
            totalItems: matchingRows.length,
            currentPage,
            perPage: PER_PAGE,
            onPageChange: (newPage) => {
                currentPage = newPage;
                applyMenuPagination();
            }
        });
    }

    // 1. CATEGORY FILTER LOGIC
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            currentCategory = this.dataset.category;
            currentPage = 1;
            applyMenuPagination();
        });
    });

    // 2. ADD ITEM (Zero reload)
    document.getElementById('saveMenuBtn')?.addEventListener('click', async function () {
        const payload = {
            itemname: document.getElementById('newName').value.trim(),
            categoryID: document.getElementById('newCategory').value,
            price: document.getElementById('newPrice').value
        };

        if (!payload.itemname || !payload.price) return alert('Name and Price are required.');

        this.disabled = true;
        this.textContent = 'Saving...';

        try {
            const res = await fetch('/menu', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                const item = data.item;
                const tr = document.createElement('tr');
                tr.id = `row-${item.menuID}`;
                tr.className = 'menu-row';
                tr.dataset.category = item.categoryID;
                tr.innerHTML = `
                    <td class="px-4 fw-bold text-secondary">${item.menuID}</td>
                    <td class="px-4 fw-bold item-name-display">${item.itemname}</td>
                    <td class="px-4 text-muted item-category-display">${item.category ? item.category.categoryname : 'None'}</td>
                    <td class="px-4 fw-bold text-success item-price-display">₱${parseFloat(item.price).toFixed(2)}</td>
                    <td class="px-4 text-end">
                        <button class="btn btn-sm btn-outline-secondary edit-btn me-1"
                                data-id="${item.menuID}"
                                data-name="${item.itemname}"
                                data-price="${item.price}"
                                data-cat="${item.categoryID}">Edit</button>
                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                data-id="${item.menuID}"
                                data-name="${item.itemname}">Delete</button>
                    </td>
                `;
                tableBody.insertBefore(tr, tableBody.firstChild);

                document.getElementById('newName').value = '';
                document.getElementById('newPrice').value = '';
                addModal.hide();

                currentPage = 1;
                applyMenuPagination();
            } else {
                const firstErr = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Failed to add item');
                alert('Error: ' + firstErr);
            }
        } catch (error) {
            alert('Error adding item.');
        } finally {
            this.disabled = false;
            this.textContent = 'Add Item';
        }
    });

    // 3. OPEN MODALS (Event Delegation)
    tableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('edit-btn')) {
            document.getElementById('editID').value = e.target.dataset.id;
            document.getElementById('editName').value = e.target.dataset.name;
            document.getElementById('editCategory').value = e.target.dataset.cat;
            document.getElementById('editPrice').value = e.target.dataset.price;
            editModal.show();
        }
        if (e.target.classList.contains('delete-btn')) {
            document.getElementById('deleteID').value = e.target.dataset.id;
            document.getElementById('deleteNameDisplay').textContent = e.target.dataset.name;
            deleteModal.show();
        }
    });

    // 4. UPDATE ITEM (Zero reload)
    document.getElementById('updateMenuBtn')?.addEventListener('click', async function () {
        const id = document.getElementById('editID').value;
        const payload = {
            itemname: document.getElementById('editName').value.trim(),
            categoryID: document.getElementById('editCategory').value,
            price: document.getElementById('editPrice').value
        };
        if (!payload.itemname || !payload.price) return alert('Required fields missing.');

        this.disabled = true;
        this.textContent = 'Saving...';

        try {
            const res = await fetch(`/menu/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                const item = data.item;
                const row = document.getElementById(`row-${id}`);
                if (row) {
                    row.dataset.category = item.categoryID;
                    row.querySelector('.item-name-display').textContent = item.itemname;
                    row.querySelector('.item-category-display').textContent = item.category ? item.category.categoryname : 'None';
                    row.querySelector('.item-price-display').textContent = `₱${parseFloat(item.price).toFixed(2)}`;

                    const editBtn = row.querySelector('.edit-btn');
                    editBtn.dataset.name = item.itemname;
                    editBtn.dataset.price = item.price;
                    editBtn.dataset.cat = item.categoryID;

                    row.querySelector('.delete-btn').dataset.name = item.itemname;
                }

                editModal.hide();
                applyMenuPagination();
            } else {
                const firstErr = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Failed to update item');
                alert('Error: ' + firstErr);
            }
        } catch (error) {
            alert('Error updating item.');
        } finally {
            this.disabled = false;
            this.textContent = 'Save Changes';
        }
    });

    // 5. DELETE ITEM
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function () {
        const id = document.getElementById('deleteID').value;
        this.disabled = true;
        this.textContent = 'Deleting...';

        try {
            const res = await fetch(`/menu/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById(`row-${id}`)?.remove();
                deleteModal.hide();
                applyMenuPagination();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete item'));
            }
        } catch (error) {
            alert('Error deleting item.');
        } finally {
            this.disabled = false;
            this.textContent = 'Delete';
        }
    });

    // Initial pagination render
    applyMenuPagination();
});