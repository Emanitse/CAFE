document.addEventListener('DOMContentLoaded', function() {
    console.log('menu.js loaded'); // Debug

    const addModal = new bootstrap.Modal(document.getElementById('addMenuModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editMenuModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteMenuModal'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // FILTER LOGIC
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const cat = this.dataset.category;
            document.querySelectorAll('.menu-row').forEach(row => {
                row.style.display = (cat === 'all' || row.dataset.category === cat) ? '' : 'none';
            });
        });
    });

    // ADD ITEM
    document.getElementById('saveMenuBtn')?.addEventListener('click', async function() {
        console.log('Save button clicked'); // Debug
        
        const payload = {
            itemname: document.getElementById('newName').value.trim(),
            categoryID: document.getElementById('newCategory').value,
            price: document.getElementById('newPrice').value
        };
        console.log('Payload:', payload); // Debug
        
        if (!payload.itemname || !payload.price) return alert('Name and Price are required.');

        this.disabled = true; this.textContent = 'Saving...';
        try {
            const res = await fetch('/menu', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            console.log('Response status:', res.status); // Debug
            const data = await res.json();
            console.log('Response data:', data); // Debug
            
            if (data.success) {
                addModal.hide();
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to add item'));
            }
        } catch (error) { 
            console.error('Error:', error); // Debug
            alert('Error adding item.'); 
        } finally { 
            this.disabled = false; 
            this.textContent = 'Add Item'; 
        }
    });

    // OPEN MODALS (Event Delegation)
    document.getElementById('menu-table-body')?.addEventListener('click', function(e) {
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

    // UPDATE ITEM
    document.getElementById('updateMenuBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('editID').value;
        const payload = {
            itemname: document.getElementById('editName').value.trim(),
            categoryID: document.getElementById('editCategory').value,
            price: document.getElementById('editPrice').value
        };
        if (!payload.itemname || !payload.price) return alert('Required fields missing.');

        this.disabled = true; this.textContent = 'Saving...';
        try {
            const res = await fetch(`/menu/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                editModal.hide();
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update item'));
            }
        } catch (error) { 
            console.error('Error:', error);
            alert('Error updating item.'); 
        } finally { 
            this.disabled = false; 
            this.textContent = 'Save Changes'; 
        }
    });

    // DELETE ITEM
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function() {
        const id = document.getElementById('deleteID').value;
        this.disabled = true; this.textContent = 'Deleting...';
        try {
            const res = await fetch(`/menu/${id}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById(`row-${id}`).remove();
                deleteModal.hide();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete item'));
            }
        } catch (error) { 
            console.error('Error:', error);
            alert('Error deleting item.'); 
        } finally { 
            this.disabled = false; 
            this.textContent = 'Delete'; 
        }
    });
});