document.addEventListener('DOMContentLoaded', function() {
    const addModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // OPEN ADD MODAL
    document.getElementById('openAddCategoryBtn')?.addEventListener('click', () => {
        addModal.show();
    });

    // 1. ADD CATEGORY
    document.getElementById('saveCategoryBtn')?.addEventListener('click', async function() {
        const nameInput = document.getElementById('newCategoryName');
        const name = nameInput.value.trim();
        if (!name) return alert('Category name is required.');

        this.disabled = true; this.textContent = 'Saving...';
        try {
            const response = await fetch('/categories', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ categoryname: name })
            });
            const data = await response.json();
            if (data.success) {
                const tbody = document.getElementById('category-table-body');
                const tr = document.createElement('tr');
                tr.id = `row-${data.category.categoryID}`;
                tr.innerHTML = `
                    <td class="px-4 fw-bold text-secondary">${data.category.categoryID}</td>
                    <td class="px-4 fw-bold category-name-display">${data.category.categoryname}</td>
                    <td class="px-4 text-end">
                        <button class="btn btn-sm btn-outline-secondary edit-btn me-1" data-id="${data.category.categoryID}" data-name="${data.category.categoryname}">Edit</button>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${data.category.categoryID}" data-name="${data.category.categoryname}">Delete</button>
                    </td>
                `;
                tbody.insertBefore(tr, tbody.firstChild);
                nameInput.value = '';
                addModal.hide();
            }
        } catch (error) { alert('Error saving category.'); } 
        finally { this.disabled = false; this.textContent = 'Add Category'; }
    });

    // 2. OPEN EDIT OR DELETE MODAL (Event Delegation)
    document.getElementById('category-table-body')?.addEventListener('click', function(e) {
        
        if (e.target.classList.contains('edit-btn')) {
            document.getElementById('editCategoryID').value = e.target.dataset.id;
            document.getElementById('editCategoryName').value = e.target.dataset.name;
            editModal.show();
        }
       
        if (e.target.classList.contains('delete-btn')) {
            document.getElementById('deleteCategoryID').value = e.target.dataset.id;
            document.getElementById('deleteCategoryName').textContent = e.target.dataset.name;
            deleteModal.show();
        }
    });

    // 3. UPDATE CATEGORY
    document.getElementById('updateCategoryBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('editCategoryID').value;
        const name = document.getElementById('editCategoryName').value.trim();
        if (!name) return alert('Category name is required.');

        this.disabled = true; this.textContent = 'Saving...';
        try {
            const response = await fetch(`/categories/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ categoryname: name })
            });
            const data = await response.json();
            if (data.success) {
                const row = document.getElementById(`row-${id}`);
                row.querySelector('.category-name-display').textContent = data.category.categoryname;
                row.querySelector('.edit-btn').dataset.name = data.category.categoryname;
                row.querySelector('.delete-btn').dataset.name = data.category.categoryname;
                editModal.hide();
            }
        } catch (error) { alert('Error updating category.'); } 
        finally { this.disabled = false; this.textContent = 'Save Changes'; }
    });

    // 4. CONFIRM DELETE CATEGORY
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('deleteCategoryID').value;
        
        this.disabled = true; this.textContent = 'Deleting...';
        try {
            const response = await fetch(`/categories/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (data.success) {
                document.getElementById(`row-${id}`).remove();
                deleteModal.hide();
            }
        } catch (error) { alert('Error deleting category.'); } 
        finally { this.disabled = false; this.textContent = 'Delete'; }
    });
});