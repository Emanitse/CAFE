document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('users-table-body');
    if (!tableBody) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const searchInput = document.getElementById('userSearchInput');
    const statusFilter = document.getElementById('userStatusFilter');

    const addModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const toggleModal = new bootstrap.Modal(document.getElementById('toggleUserModal'));

    const addAlert = document.getElementById('addUserAlert');
    const editAlert = document.getElementById('editUserAlert');

    const PER_PAGE = 10;
    let allUsers = [];
    let currentPage = 1;
    let originalUser = { username: '', email: '' };
    let searchTimeout = null;

    function showAlert(alertEl, message, type = 'danger') {
        alertEl.className = `alert alert-${type} py-2 small mb-3`;
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
    }

    function hideAlert(alertEl) {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
    }

    // 1. Render current page slice (10 per page)
    function renderUsersPage() {
        const totalPages = Math.max(1, Math.ceil(allUsers.length / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;

        window.renderTablePagination({
            containerId: 'users-pagination',
            totalItems: allUsers.length,
            currentPage,
            perPage: PER_PAGE,
            onPageChange: (newPage) => {
                currentPage = newPage;
                renderUsersPage();
            }
        });

        if (allUsers.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">No user accounts found.</td>
                </tr>
            `;
            return;
        }

        const startIndex = (currentPage - 1) * PER_PAGE;
        const pageUsers = allUsers.slice(startIndex, startIndex + PER_PAGE);

        tableBody.innerHTML = pageUsers.map(user => {
            const roleBadgeClass = user.role === 'superadmin'
                ? 'bg-dark'
                : (user.role === 'admin' ? 'bg-coffee' : 'bg-secondary');

            const statusBadge = user.is_active
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Deactivated</span>`;

            const selfTag = user.is_self
                ? `<span class="badge bg-light text-dark border ms-1">You</span>`
                : '';

            const toggleBtn = user.is_self
                ? `<button class="btn btn-sm btn-outline-secondary" disabled>Active</button>`
                : (user.is_active
                    ? `<button class="btn btn-sm btn-outline-danger toggle-status-btn" data-id="${user.accountID}" data-name="${user.username}" data-active="1">De-Activate</button>`
                    : `<button class="btn btn-sm btn-outline-success toggle-status-btn" data-id="${user.accountID}" data-name="${user.username}" data-active="0">Re-Activate</button>`);

            return `
                <tr id="user-row-${user.accountID}">
                    <td class="px-4 fw-bold text-secondary">${user.accountID}</td>
                    <td class="px-4 fw-bold text-dark">${user.username}${selfTag}</td>
                    <td class="px-4 text-muted">${user.email}</td>
                    <td class="px-4">
                        <span class="badge ${roleBadgeClass} text-capitalize">${user.role}</span>
                    </td>
                    <td class="px-4">${statusBadge}</td>
                    <td class="px-4 text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary edit-user-btn me-1"
                                data-id="${user.accountID}"
                                data-username="${user.username}"
                                data-email="${user.email}">
                            Edit
                        </button>
                        ${toggleBtn}
                    </td>
                </tr>
            `;
        }).join('');
    }

    // 2. Fetch users from backend
    async function loadUsers(resetPage = true) {
        if (resetPage) currentPage = 1;

        const params = new URLSearchParams({
            search: searchInput.value.trim(),
            status: statusFilter.value,
        });

        try {
            const res = await fetch(`/users/list?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                allUsers = data.users;
                renderUsersPage();
            }
        } catch (err) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-4">Failed to load users.</td>
                </tr>
            `;
        }
    }

    // 3. Open & Submit Add User Modal
    document.getElementById('openAddUserBtn')?.addEventListener('click', function () {
        document.getElementById('addUsername').value = '';
        document.getElementById('addEmail').value = '';
        document.getElementById('addPassword').value = '';
        const roleSelect = document.getElementById('addRole');
        if (roleSelect && roleSelect.tagName === 'SELECT') {
            roleSelect.value = 'staff';
        }
        hideAlert(addAlert);
        addModal.show();
    });

    document.getElementById('saveNewUserBtn')?.addEventListener('click', async function () {
        hideAlert(addAlert);

        const username = document.getElementById('addUsername').value.trim();
        const email = document.getElementById('addEmail').value.trim();
        const role = document.getElementById('addRole').value;
        const password = document.getElementById('addPassword').value;

        if (!username || !email || !password) {
            return showAlert(addAlert, 'All fields are required.');
        }

        this.disabled = true;
        this.textContent = 'Adding...';

        try {
            const res = await fetch('/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ username, email, role, password })
            });

            const data = await res.json();

            if (!res.ok) {
                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'Validation failed.');
                return showAlert(addAlert, firstError);
            }

            if (data.success) {
                addModal.hide();
                await loadUsers(false);
                // Jump to the last page so the newly added user is immediately visible
                currentPage = Math.max(1, Math.ceil(allUsers.length / PER_PAGE));
                renderUsersPage();
            }
        } catch (err) {
            showAlert(addAlert, 'An error occurred while creating the user.');
        } finally {
            this.disabled = false;
            this.textContent = 'Add User';
        }
    });

    // 4. Search & Filter Listeners
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadUsers(true), 300);
    });

    statusFilter.addEventListener('change', () => loadUsers(true));

    // 5. Table Action Buttons (Event Delegation)
    tableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('edit-user-btn')) {
            const btn = e.target;
            originalUser = {
                username: btn.dataset.username,
                email: btn.dataset.email,
            };

            document.getElementById('editUserID').value = btn.dataset.id;
            document.getElementById('editUsername').value = btn.dataset.username;
            document.getElementById('editEmail').value = btn.dataset.email;
            document.getElementById('editPassword').value = '';
            hideAlert(editAlert);

            editModal.show();
        }

        if (e.target.classList.contains('toggle-status-btn')) {
            const btn = e.target;
            const isCurrentlyActive = btn.dataset.active === '1';

            document.getElementById('toggleUserID').value = btn.dataset.id;
            const confirmBtn = document.getElementById('confirmToggleUserBtn');

            if (isCurrentlyActive) {
                document.getElementById('toggleModalTitle').textContent = 'De-Activate Account?';
                document.getElementById('toggleModalMessage').innerHTML =
                    `Are you sure you want to deactivate <strong>${btn.dataset.name}</strong>? They will be prevented from logging in.`;
                confirmBtn.className = 'btn btn-danger fw-bold px-3';
                confirmBtn.textContent = 'De-Activate';
            } else {
                document.getElementById('toggleModalTitle').textContent = 'Re-Activate Account?';
                document.getElementById('toggleModalMessage').innerHTML =
                    `Restore login access for <strong>${btn.dataset.name}</strong>?`;
                confirmBtn.className = 'btn btn-success fw-bold px-3';
                confirmBtn.textContent = 'Re-Activate';
            }

            toggleModal.show();
        }
    });

    // 6. Save User Edits (Keeps current page intact)
    document.getElementById('saveUserChangesBtn').addEventListener('click', async function () {
        hideEditAlert();

        const id = document.getElementById('editUserID').value;
        const username = document.getElementById('editUsername').value.trim();
        const email = document.getElementById('editEmail').value.trim();
        const password = document.getElementById('editPassword').value;

        if (!username || !email) {
            return showEditAlert('Username and email are required.');
        }

        if (
            username === originalUser.username &&
            email.toLowerCase() === originalUser.email.toLowerCase() &&
            password === ''
        ) {
            showEditAlert('No changes were made.', 'secondary');
            setTimeout(() => editModal.hide(), 700);
            return;
        }

        this.disabled = true;
        this.textContent = 'Saving...';

        try {
            const res = await fetch(`/users/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ username, email, password })
            });

            const data = await res.json();

            if (!res.ok) {
                const firstError = data.errors
                    ? Object.values(data.errors)[0][0]
                    : (data.message || 'Validation failed.');
                return showEditAlert(firstError);
            }

            if (data.unchanged) {
                showEditAlert(data.message, 'secondary');
                setTimeout(() => editModal.hide(), 700);
                return;
            }

            if (data.success) {
                editModal.hide();
                loadUsers(false);
            }
        } catch (err) {
            showEditAlert('An error occurred while updating the user.');
        } finally {
            this.disabled = false;
            this.textContent = 'Save Changes';
        }
    });

    // 7. Confirm De-Activate / Re-Activate (Keeps current page intact)
    document.getElementById('confirmToggleUserBtn').addEventListener('click', async function () {
        const id = document.getElementById('toggleUserID').value;
        const originalText = this.textContent;

        this.disabled = true;
        this.textContent = 'Processing...';

        try {
            const res = await fetch(`/users/${id}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            });

            const data = await res.json();
            if (data.success) {
                toggleModal.hide();
                loadUsers(false);
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (err) {
            alert('Error updating account status.');
        } finally {
            this.disabled = false;
            this.textContent = originalText;
        }
    });

    // Initial load
    loadUsers(true);
});