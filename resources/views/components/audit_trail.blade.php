@extends('layouts.app')

@section('content')
<div class="container-fluid h-100 d-flex flex-column pb-3">

    <!-- Header & Universal Export Toggle -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">System Audit Trail</h3>
            <small class="text-muted">Superadmin security monitoring for authentication access and application errors</small>
        </div>

        <button type="button" id="toggleAuditExportModeBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-4 py-2">
            Select to Export
        </button>
    </div>

    <!-- Navigation Tabs for the 2 Tables -->
    <ul class="nav nav-tabs mb-3" id="auditTrailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark d-flex align-items-center gap-2"
                    id="access-logs-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#access-logs-pane"
                    data-tab-type="access"
                    type="button"
                    role="tab">
                Access Logs
                <span class="badge bg-coffee" id="tab-badge-access">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark d-flex align-items-center gap-2"
                    id="error-logs-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#error-logs-pane"
                    data-tab-type="error"
                    type="button"
                    role="tab">
                Error Logs
                <span class="badge bg-danger" id="tab-badge-error">0</span>
            </button>
        </li>
    </ul>

    <div class="tab-content flex-grow-1 overflow-hidden">

        <!-- ========================================================= -->
        <!-- TAB 1: ACCESS LOGS                                        -->
        <!-- ========================================================= -->
        <div class="tab-pane fade show active h-100" id="access-logs-pane" role="tabpanel">
            <div class="d-flex flex-column h-100 overflow-hidden">

                <!-- Summary Badges -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Total Events</small>
                            <span class="fw-bold text-dark" id="access-sum-total">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Successful</small>
                            <span class="fw-bold text-success" id="access-sum-success">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Failed</small>
                            <span class="fw-bold text-danger" id="access-sum-failed">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Warnings / Lockouts</small>
                            <span class="fw-bold text-warning" id="access-sum-warning">0</span>
                        </div>
                    </div>
                </div>

                <!-- Access Logs Filter Bar -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label for="accessSearch" class="form-label fw-bold small text-muted mb-1">Search</label>
                                <input type="text" id="accessSearch" class="form-control bg-light" placeholder="Username, email, IP, event, details...">
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="accessStatusFilter" class="form-label fw-bold small text-muted mb-1">Status</label>
                                <select id="accessStatusFilter" class="form-select bg-light">
                                    <option value="all">All Status</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                    <option value="warning">Warning / Lockout</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="accessEventFilter" class="form-label fw-bold small text-muted mb-1">Event Type</label>
                                <select id="accessEventFilter" class="form-select bg-light">
                                    <option value="all">All Events</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="accessFromDate" class="form-label fw-bold small text-muted mb-1">From Date</label>
                                <input type="date" id="accessFromDate" class="form-control bg-light">
                            </div>

                            <div class="col-6 col-md-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="accessToDate" class="form-label fw-bold small text-muted mb-0">To Date</label>
                                    <button type="button" id="resetAccessFilters" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fw-bold" style="font-size: 0.75rem;">Reset</button>
                                </div>
                                <input type="date" id="accessToDate" class="form-control bg-light">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Access Cross-Page Selection Bar -->
                <div id="accessSelectionBar" class="alert bg-white border shadow-sm py-2 px-3 mb-2 d-none d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAllAccessCheckbox" style="cursor: pointer;">
                            <label class="form-check-label fw-bold small text-dark" for="selectAllAccessCheckbox" style="cursor: pointer;">
                                Select All (<span id="totalAccessSelectableCount">0</span> across all pages)
                            </label>
                        </div>
                        <button type="button" id="clearAccessSelectionBtn" class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-bold">
                            Clear
                        </button>
                    </div>
                    <span class="badge bg-coffee px-3 py-2" id="selectedAccessBadge">0 selected</span>
                </div>

                <!-- Access Logs Table -->
                <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
                    <div class="card-body p-0 d-flex flex-column overflow-hidden">
                        <div class="table-responsive flex-grow-1">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="px-3 py-3 text-center access-select-col d-none" style="width: 45px;">
                                            <input class="form-check-input" type="checkbox" id="selectCurrentAccessPageCheckbox" style="cursor: pointer;">
                                        </th>
                                        <th class="px-3 py-3 text-muted">Timestamp</th>
                                        <th class="px-3 py-3 text-muted">User / Email</th>
                                        <th class="px-3 py-3 text-muted">Role</th>
                                        <th class="px-3 py-3 text-muted">Event</th>
                                        <th class="px-3 py-3 text-muted">Status</th>
                                        <th class="px-3 py-3 text-muted">IP Address</th>
                                        <th class="px-3 py-3 text-muted">Details</th>
                                        <th class="px-3 py-3 text-muted text-end">Inspect</th>
                                    </tr>
                                </thead>
                                <tbody id="access-logs-table-body">
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">Loading access logs...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="access-logs-pagination" class="card-footer bg-white border-top py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2"></div>
                    </div>
                </div>

                <!-- Access Bottom Export Bar -->
                <div id="accessExportBottomBar" class="d-none d-flex justify-content-end align-items-center gap-2 mt-3">
                    <button type="button" class="btn btn-light border fw-bold px-4 cancel-audit-export-btn">Cancel</button>
                    <button type="button" id="exportAccessPdfBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-4 py-2" disabled>
                        Export Access Logs PDF (0)
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 2: ERROR LOGS                                         -->
        <!-- ========================================================= -->
        <div class="tab-pane fade h-100" id="error-logs-pane" role="tabpanel">
            <div class="d-flex flex-column h-100 overflow-hidden">

                <!-- Summary Badges -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Total Logged</small>
                            <span class="fw-bold text-dark" id="error-sum-total">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Critical / Alert</small>
                            <span class="fw-bold text-dark" id="error-sum-critical">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Errors</small>
                            <span class="fw-bold text-danger" id="error-sum-error">0</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-white border rounded px-3 py-2 shadow-sm d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">Warnings</small>
                            <span class="fw-bold text-warning" id="error-sum-warning">0</span>
                        </div>
                    </div>
                </div>

                <!-- Error Logs Filter Bar -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-5">
                                <label for="errorSearch" class="form-label fw-bold small text-muted mb-1">Search</label>
                                <input type="text" id="errorSearch" class="form-control bg-light" placeholder="Error message, exception, file, route URL, user...">
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="errorLevelFilter" class="form-label fw-bold small text-muted mb-1">Severity Level</label>
                                <select id="errorLevelFilter" class="form-select bg-light">
                                    <option value="all">All Levels</option>
                                    <option value="critical">Critical</option>
                                    <option value="error">Error</option>
                                    <option value="warning">Warning</option>
                                </select>
                            </div>

                            <div class="col-6 col-md-2">
                                <label for="errorFromDate" class="form-label fw-bold small text-muted mb-1">From Date</label>
                                <input type="date" id="errorFromDate" class="form-control bg-light">
                            </div>

                            <div class="col-6 col-md-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="errorToDate" class="form-label fw-bold small text-muted mb-0">To Date</label>
                                    <button type="button" id="resetErrorFilters" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fw-bold" style="font-size: 0.75rem;">Reset</button>
                                </div>
                                <input type="date" id="errorToDate" class="form-control bg-light">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Cross-Page Selection Bar -->
                <div id="errorSelectionBar" class="alert bg-white border shadow-sm py-2 px-3 mb-2 d-none d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="selectAllErrorCheckbox" style="cursor: pointer;">
                            <label class="form-check-label fw-bold small text-dark" for="selectAllErrorCheckbox" style="cursor: pointer;">
                                Select All (<span id="totalErrorSelectableCount">0</span> across all pages)
                            </label>
                        </div>
                        <button type="button" id="clearErrorSelectionBtn" class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-bold">
                            Clear
                        </button>
                    </div>
                    <span class="badge bg-coffee px-3 py-2" id="selectedErrorBadge">0 selected</span>
                </div>

                <!-- Error Logs Table -->
                <div class="card border-0 shadow-sm flex-grow-1 overflow-hidden">
                    <div class="card-body p-0 d-flex flex-column overflow-hidden">
                        <div class="table-responsive flex-grow-1">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="px-3 py-3 text-center error-select-col d-none" style="width: 45px;">
                                            <input class="form-check-input" type="checkbox" id="selectCurrentErrorPageCheckbox" style="cursor: pointer;">
                                        </th>
                                        <th class="px-3 py-3 text-muted">Timestamp</th>
                                        <th class="px-3 py-3 text-muted">Level</th>
                                        <th class="px-3 py-3 text-muted">Message</th>
                                        <th class="px-3 py-3 text-muted">Source / Location</th>
                                        <th class="px-3 py-3 text-muted">User</th>
                                        <th class="px-3 py-3 text-muted text-end">Inspect</th>
                                    </tr>
                                </thead>
                                <tbody id="error-logs-table-body">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">Loading error logs...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="error-logs-pagination" class="card-footer bg-white border-top py-2 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2"></div>
                    </div>
                </div>

                <!-- Error Bottom Export Bar -->
                <div id="errorExportBottomBar" class="d-none d-flex justify-content-end align-items-center gap-2 mt-3">
                    <button type="button" class="btn btn-light border fw-bold px-4 cancel-audit-export-btn">Cancel</button>
                    <button type="button" id="exportErrorPdfBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-4 py-2" disabled>
                        Export Error Logs PDF (0)
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- LOG DETAIL INSPECTOR MODAL -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold" id="auditDetailModalTitle">Log Entry Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="auditDetailModalBody"></div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection