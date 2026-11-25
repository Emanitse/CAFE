@extends('layouts.app')

@section('content')
<div class="container-fluid h-100 d-flex flex-column">

    <!-- Header & Top-Right Actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">User Management</h3>
            <small class="text-muted">Manage staff credentials and account access</small>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="text" id="userSearchInput" class="form-control bg-white shadow-sm" style="width: 220px;" placeholder="Search username or email...">
            <select id="userStatusFilter" class="form-select bg-white shadow-sm" style="width: 145px;">
                <option value="all">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Deactivated</option>
            </select>
            <button type="button" class="btn text-white bg-coffee shadow-sm px-4 fw-bold" id="openAddUserBtn">
                + Add User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
        <div class="card-body p-0 d-flex flex-column overflow-hidden">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="px-4 py-3 text-muted">ID</th>
                            <th class="px-4 py-3 text-muted">Username</th>
                            <th class="px-4 py-3 text-muted">Email</th>
                            <th class="px-4 py-3 text-muted">Role</th>
                            <th class="px-4 py-3 text-muted">Status</th>
                            <th class="px-4 py-3 text-muted">2FA</th>
                            <th class="px-4 py-3 text-muted text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="users-pagination" class="card-footer bg-white border-top py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2"></div>
        </div>
    </div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="addUserAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

                <div class="mb-3">
                    <label for="addUsername" class="fw-bold mb-1">Username</label>
                    <input type="text" id="addUsername" class="form-control bg-light" placeholder="e.g. jdoe" required>
                </div>

                <div class="mb-3">
                    <label for="addEmail" class="fw-bold mb-1">Email Address</label>
                    <input type="email" id="addEmail" class="form-control bg-light" placeholder="e.g. jdoe@cafe.com" required>
                </div>

                <div class="mb-3">
                    <label for="addRole" class="fw-bold mb-1">Role</label>
                    @if(session('role') === 'superadmin')
                        <select id="addRole" class="form-select bg-light">
                            <option value="staff" selected>Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    @else
                        <input type="text" class="form-control bg-light text-muted" value="Staff" readonly>
                        <input type="hidden" id="addRole" value="staff">
                    @endif
                </div>

                <div class="mb-4">
                    <label for="addPassword" class="fw-bold mb-1">Password</label>
                    <input type="password" id="addPassword" class="form-control bg-light" placeholder="Min. 8 characters" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveNewUserBtn" class="btn text-white bg-coffee fw-bold px-4">Add User</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">Edit User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editUserID">

                <div id="editUserAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

                <div class="mb-3">
                    <label for="editUsername" class="fw-bold mb-1">Username</label>
                    <input type="text" id="editUsername" class="form-control bg-light" required>
                </div>

                <div class="mb-3">
                    <label for="editEmail" class="fw-bold mb-1">Email Address</label>
                    <input type="email" id="editEmail" class="form-control bg-light" required>
                </div>

                <div class="mb-4">
                    <label for="editPassword" class="fw-bold mb-1">
                        New Password <span class="text-muted fw-normal small">(leave blank to keep current)</span>
                    </label>
                    <input type="password" id="editPassword" class="form-control bg-light" placeholder="Min. 8 characters">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveUserChangesBtn" class="btn text-white bg-coffee fw-bold px-4">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DEACTIVATE / REACTIVATE CONFIRMATION MODAL -->
<div class="modal fade" id="toggleUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow text-center p-3">
            <div class="modal-body">
                <input type="hidden" id="toggleUserID">
                <h5 class="fw-bold mb-2" id="toggleModalTitle">Deactivate Account?</h5>
                <p class="text-muted small mb-4" id="toggleModalMessage"></p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light fw-bold px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmToggleUserBtn" class="btn btn-danger fw-bold px-3">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SUPERADMIN RESET 2FA CONFIRMATION MODAL -->
<div class="modal fade" id="reset2faModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow text-center p-3">
            <div class="modal-body">
                <input type="hidden" id="reset2faUserID">
                <h5 class="fw-bold mb-2">Reset User 2FA?</h5>
                <p class="text-muted small mb-4" id="reset2faModalMessage"></p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light fw-bold px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmReset2faBtn" class="btn btn-warning fw-bold px-3">Reset 2FA</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection