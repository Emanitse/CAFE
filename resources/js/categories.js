document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Initialize BOTH modals via JS
    const addModal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));

    // 1. OPEN ADD MODAL
    document.getElementById('openAddCategoryBtn')?.addEventListener('click', () => {
        addModal.show();
    });

    // 2. ADD CATEGORY
    document.getElementById('saveCategoryBtn')?.addEventListener('click', async function() {
        const nameInput = document.getElementById('newCategoryName');
        const name = nameInput.value.trim();
        
        if (!name) return alert('Category name is required.');

        this.disabled = true;
        this.textContent = 'Saving...';

        try {
            const response = await fetch('/categories', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
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
                        <button class="btn btn-sm btn-outline-secondary edit-btn" 
                                data-id="${data.category.categoryID}" 
                                data-name="${data.category.categoryname}">
                            Edit
                        </button>
                    </td>
                `;
                tbody.insertBefore(tr, tbody.firstChild);

                nameInput.value = '';
                addModal.hide();
            }
        } catch (error) {
            alert('Error saving category.');
        } finally {
            this.disabled = false;
            this.textContent = 'Add Category';
        }
    });

    // 3. OPEN EDIT MODAL (Event Delegation)
    document.getElementById('category-table-body')?.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-btn')) {
            document.getElementById('editCategoryID').value = e.target.dataset.id;
            document.getElementById('editCategoryName').value = e.target.dataset.name;
            editModal.show();
        }
    });

    // 4. UPDATE CATEGORY
    document.getElementById('updateCategoryBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('editCategoryID').value;
        const nameInput = document.getElementById('editCategoryName');
        const name = nameInput.value.trim();

        if (!name) return alert('Category name is required.');

        this.disabled = true;
        this.textContent = 'Saving...';

        try {
            const response = await fetch(`/categories/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ categoryname: name })
            });

            const data = await response.json();
            
            if (data.success) {
                const row = document.getElementById(`row-${id}`);
                row.querySelector('.category-name-display').textContent = data.category.categoryname;
                row.querySelector('.edit-btn').dataset.name = data.category.categoryname;

                editModal.hide();
            }
        } catch (error) {
            alert('Error updating category.');
        } finally {
            this.disabled = false;
            this.textContent = 'Save Changes';
        }
    });
});