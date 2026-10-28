@extends('layouts.app')

@section('content')
<div class="container-fluid h-100 d-flex flex-column">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-dark mb-0">Menu Management</h3>
        <button class="btn text-white bg-coffee shadow-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addMenuModal">
            + Add Menu Item
        </button>
    </div>

    <!-- Category Filters -->
    <div class="mb-3" id="category-filters">
        <button class="btn category-btn shadow-sm me-2 mb-2 active" data-category="all">All</button>
        @foreach ($categories as $category)
            <button class="btn category-btn shadow-sm me-2 mb-2" data-category="{{ $category->categoryID }}">
                {{ $category->categoryname }}
            </button>
        @endforeach
    </div>

    <!-- Menu Items Table -->
    <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
        <div class="card-body p-0 d-flex flex-column">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="px-4 py-3 text-muted">ID</th>
                            <th class="px-4 py-3 text-muted">Item Name</th>
                            <th class="px-4 py-3 text-muted">Category</th>
                            <th class="px-4 py-3 text-muted">Price</th>
                            <th class="px-4 py-3 text-muted text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menu-table-body">
                        @foreach($menuItems as $item)
                            <!-- Notice the data-category attribute here for the filter logic -->
                            <tr id="row-{{ $item->menuID }}" class="menu-row" data-category="{{ $item->categoryID }}">
                                <td class="px-4 fw-bold text-secondary">{{ $item->menuID }}</td>
                                <td class="px-4 fw-bold item-name-display">{{ $item->itemname }}</td>
                                <td class="px-4 text-muted item-category-display">{{ $item->category->categoryname ?? 'None' }}</td>
                                <td class="px-4 fw-bold text-success item-price-display">₱{{ number_format($item->price, 2) }}</td>
                                <td class="px-4 text-end">
                                    <button class="btn btn-sm btn-outline-secondary edit-btn me-1" 
                                            data-id="{{ $item->menuID }}" 
                                            data-name="{{ $item->itemname }}"
                                            data-price="{{ $item->price }}"
                                            data-cat="{{ $item->categoryID }}">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                            data-id="{{ $item->menuID }}" 
                                            data-name="{{ $item->itemname }}">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">New Menu Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="fw-bold mb-1">Item Name</label>
                    <input type="text" id="newName" class="form-control bg-light">
                </div>
                <div class="mb-3">
                    <label class="fw-bold mb-1">Category</label>
                    <select id="newCategory" class="form-select bg-light">
                        @foreach($categories as $category)
                            <option value="{{ $category->categoryID }}">{{ $category->categoryname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="fw-bold mb-1">Price (₱)</label>
                    <input type="number" id="newPrice" class="form-control bg-light" step="0.01" min="0">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveMenuBtn" class="btn text-white bg-coffee fw-bold px-4">Add Item</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold">Edit Menu Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editID">
                <div class="mb-3">
                    <label class="fw-bold mb-1">Item Name</label>
                    <input type="text" id="editName" class="form-control bg-light">
                </div>
                <div class="mb-3">
                    <label class="fw-bold mb-1">Category</label>
                    <select id="editCategory" class="form-select bg-light">
                        @foreach($categories as $category)
                            <option value="{{ $category->categoryID }}">{{ $category->categoryname }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="fw-bold mb-1">Price (₱)</label>
                    <input type="number" id="editPrice" class="form-control bg-light" step="0.01" min="0">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="updateMenuBtn" class="btn text-white bg-coffee fw-bold px-4">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow text-center p-3">
            <div class="modal-body">
                <input type="hidden" id="deleteID">
                <h5 class="fw-bold mb-3">Delete Item?</h5>
                <p class="text-muted mb-4">Remove "<span id="deleteNameDisplay" class="fw-bold text-dark"></span>"? This hides it from the POS system.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger fw-bold px-4">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

