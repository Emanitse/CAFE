@extends('layouts.app')

@section('content')
<div class="container-fluid h-100 d-flex flex-column">
    
    <!-- Header Area -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Category Management</h3>
        <!-- Top Right Add Button -->
        <button class="btn text-white bg-coffee shadow-sm px-4 fw-bold" id="openAddCategoryBtn">
            + Add Category
        </button>
    </div>

    <!-- Categories Table -->
    <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
        <div class="card-body p-0 d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="px-4 py-3 text-muted">ID</th>
                            <th class="px-4 py-3 text-muted w-75">Category Name</th>
                            <th class="px-4 py-3 text-muted text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="category-table-body">
                        @foreach($categories as $category)
                            <tr id="row-{{ $category->categoryID }}">
                                <td class="px-4 fw-bold text-secondary">{{ $category->categoryID }}</td>
                                <td class="px-4 fw-bold category-name-display">{{ $category->categoryname }}</td>
                                <td class="px-4 text-end">
    <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
            data-id="{{ $category->categoryID }}" 
            data-name="{{ $category->categoryname }}">
        Edit
    </button>
    <button class="btn btn-sm btn-outline-danger delete-btn" 
            data-id="{{ $category->categoryID }}" 
            data-name="{{ $category->categoryname }}">
        Delete
    </button>
</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ADD CATEGORY MODAL -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="fw-bold mb-2">Category Name</label>
                    <input type="text" id="newCategoryName" class="form-control form-control-lg bg-light" placeholder="e.g. Hot Coffee">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveCategoryBtn" class="btn text-white bg-coffee fw-bold px-4">Add Category</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">Edit Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editCategoryID">
                <div class="mb-4">
                    <label class="fw-bold mb-2">Category Name</label>
                    <input type="text" id="editCategoryName" class="form-control form-control-lg bg-light">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="updateCategoryBtn" class="btn text-white bg-coffee fw-bold px-4">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow text-center p-3">
            <div class="modal-body">
                <input type="hidden" id="deleteCategoryID">
                <h5 class="fw-bold mb-3">Delete Category?</h5>
                <p class="text-muted mb-4">Are you sure you want to remove "<span id="deleteCategoryName" class="fw-bold text-dark"></span>"? This cannot be undone.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger fw-bold px-4">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

