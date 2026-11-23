@extends('layouts.app')

@section('content')
<div class="container-fluid h-100 d-flex flex-column">

    <!-- Header, Live Summary Badges & Universal Export Toggle -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">Sales Report & Audit Trail</h3>
            <small class="text-muted">Transaction item logs across orders</small>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="bg-white border rounded px-3 py-2 shadow-sm text-end">
                <small class="text-muted d-block" style="font-size: 0.75rem;">Entries</small>
                <span class="fw-bold text-dark" id="summary-count">0</span>
            </div>
            <div class="bg-white border rounded px-3 py-2 shadow-sm text-end">
                <small class="text-muted d-block" style="font-size: 0.75rem;">Items Sold</small>
                <span class="fw-bold text-dark" id="summary-qty">0</span>
            </div>
            <div class="bg-coffee text-white rounded px-3 py-2 shadow-sm text-end">
                <small class="opacity-75 d-block" style="font-size: 0.75rem;">Filtered Sales</small>
                <span class="fw-bold" id="summary-sales">₱0.00</span>
            </div>

            <!-- Universal Export Mode Toggle Button -->
            <button type="button" id="toggleExportModeBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-3 py-2 ms-1">
                Select to Export
            </button>
        </div>
    </div>

    <!-- Combined Filter Bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="reportSearch" class="form-label fw-bold small text-muted mb-1">Search</label>
                    <input type="text" id="reportSearch" class="form-control bg-light" placeholder="Order #, item, cashier, method...">
                </div>

                <div class="col-6 col-md-2">
                    <label for="reportCategoryFilter" class="form-label fw-bold small text-muted mb-1">Category</label>
                    <select id="reportCategoryFilter" class="form-select bg-light">
                        <option value="all">All Categories</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label for="reportItemFilter" class="form-label fw-bold small text-muted mb-1">Item</label>
                    <select id="reportItemFilter" class="form-select bg-light">
                        <option value="all">All Items</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label for="reportFromDate" class="form-label fw-bold small text-muted mb-1">From Date</label>
                    <input type="date" id="reportFromDate" class="form-control bg-light">
                </div>

                <div class="col-6 col-md-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="reportToDate" class="form-label fw-bold small text-muted mb-0">To Date</label>
                        <button type="button" id="resetReportFilters" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fw-bold" style="font-size: 0.75rem;">Reset</button>
                    </div>
                    <input type="date" id="reportToDate" class="form-control bg-light">
                </div>
            </div>
        </div>
    </div>

    <!-- Cross-Page Selection Bar (Visible only in Export Selection Mode) -->
    <div id="exportSelectionBar" class="alert bg-white border shadow-sm py-2 px-3 mb-2 d-none d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="selectAllFilteredCheckbox" style="cursor: pointer;">
                <label class="form-check-label fw-bold small text-dark" for="selectAllFilteredCheckbox" style="cursor: pointer;">
                    Select All (<span id="totalFilteredSelectableCount">0</span> across all pages)
                </label>
            </div>
            <button type="button" id="clearSelectionBtn" class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-bold">
                Clear
            </button>
        </div>
        <span class="badge bg-coffee px-3 py-2" id="selectedRowBadge">0 selected</span>
    </div>

    <!-- Audit Trail Table -->
    <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
        <div class="card-body p-0 d-flex flex-column overflow-hidden">
            <div class="table-responsive flex-grow-1">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="px-3 py-3 text-center select-col d-none" style="width: 45px;">
                                <input class="form-check-input" type="checkbox" id="selectCurrentPageCheckbox" title="Select current page" style="cursor: pointer;">
                            </th>
                            <th class="px-4 py-3 text-muted">Date & Time</th>
                            <th class="px-4 py-3 text-muted">Order ID</th>
                            <th class="px-4 py-3 text-muted">Item</th>
                            <th class="px-4 py-3 text-muted">Category</th>
                            <th class="px-4 py-3 text-muted text-center">Qty</th>
                            <th class="px-4 py-3 text-muted text-end">Subtotal</th>
                            <th class="px-4 py-3 text-muted">Payment</th>
                            <th class="px-4 py-3 text-muted">Cashier</th>
                        </tr>
                    </thead>
                    <tbody id="reports-table-body">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">Loading audit trail...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="reports-pagination" class="card-footer bg-white border-top py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2"></div>
        </div>
    </div>

   
    <div id="exportBottomBar" class="d-none d-flex justify-content-end align-items-center gap-2 mt-3">
        <button type="button" id="cancelExportModeBtn" class="btn btn-light border fw-bold px-4">
            Cancel
        </button>
        <button type="button" id="exportPdfBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-4 py-2" disabled>
            Export PDF (0)
        </button>
    </div>

</div>
@endsection