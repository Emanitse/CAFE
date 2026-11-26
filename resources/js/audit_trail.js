document.addEventListener('DOMContentLoaded', function () {
    const accessTableBody = document.getElementById('access-logs-table-body');
    const errorTableBody = document.getElementById('error-logs-table-body');
    if (!accessTableBody || !errorTableBody) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const toggleExportBtn = document.getElementById('toggleAuditExportModeBtn');
    const detailModal = new bootstrap.Modal(document.getElementById('auditDetailModal'));
    const detailModalTitle = document.getElementById('auditDetailModalTitle');
    const detailModalBody = document.getElementById('auditDetailModalBody');

    const PER_PAGE = 10;
    let isExportMode = false;

    // Independent states for Access Logs & Error Logs
    const state = {
        access: {
            logs: [],
            page: 1,
            selectedIds: new Set(),
            searchTimeout: null,
        },
        error: {
            logs: [],
            page: 1,
            selectedIds: new Set(),
            searchTimeout: null,
        }
    };

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function submitPdfExport(actionUrl, selectedSet, dateRangeLabel, filterLabel) {
        if (selectedSet.size === 0) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = actionUrl;
        form.style.display = 'none';

        const fields = {
            _token: csrfToken,
            selected_ids: JSON.stringify(Array.from(selectedSet)),
            date_range_label: dateRangeLabel,
            filter_label: filterLabel,
        };

        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    // ---------------------------------------------------------
    // GLOBAL EXPORT SELECTION MODE TOGGLE
    // ---------------------------------------------------------
    function setExportMode(active) {
        isExportMode = active;

        if (isExportMode) {
            toggleExportBtn.textContent = 'Exit Selection';
            toggleExportBtn.classList.remove('bg-coffee', 'text-white');
            toggleExportBtn.classList.add('btn-outline-dark');

            document.getElementById('accessSelectionBar').classList.remove('d-none');
            document.getElementById('accessExportBottomBar').classList.remove('d-none');
            document.querySelectorAll('.access-select-col').forEach(el => el.classList.remove('d-none'));

            document.getElementById('errorSelectionBar').classList.remove('d-none');
            document.getElementById('errorExportBottomBar').classList.remove('d-none');
            document.querySelectorAll('.error-select-col').forEach(el => el.classList.remove('d-none'));
        } else {
            state.access.selectedIds.clear();
            state.error.selectedIds.clear();

            toggleExportBtn.textContent = 'Select to Export';
            toggleExportBtn.classList.remove('btn-outline-dark');
            toggleExportBtn.classList.add('bg-coffee', 'text-white');

            document.getElementById('accessSelectionBar').classList.add('d-none');
            document.getElementById('accessExportBottomBar').classList.add('d-none');
            document.querySelectorAll('.access-select-col').forEach(el => el.classList.add('d-none'));

            document.getElementById('errorSelectionBar').classList.add('d-none');
            document.getElementById('errorExportBottomBar').classList.add('d-none');
            document.querySelectorAll('.error-select-col').forEach(el => el.classList.add('d-none'));
        }

        renderAccessPage();
        renderErrorPage();
    }

    toggleExportBtn.addEventListener('click', () => setExportMode(!isExportMode));
    document.querySelectorAll('.cancel-audit-export-btn').forEach(btn => {
        btn.addEventListener('click', () => setExportMode(false));
    });

    // ---------------------------------------------------------
    // TAB 1: ACCESS LOGS LOGIC
    // ---------------------------------------------------------
    const accessSearch = document.getElementById('accessSearch');
    const accessStatusFilter = document.getElementById('accessStatusFilter');
    const accessEventFilter = document.getElementById('accessEventFilter');
    const accessFromDate = document.getElementById('accessFromDate');
    const accessToDate = document.getElementById('accessToDate');

    const selectAllAccessCheckbox = document.getElementById('selectAllAccessCheckbox');
    const selectCurrentAccessPageCheckbox = document.getElementById('selectCurrentAccessPageCheckbox');
    const exportAccessPdfBtn = document.getElementById('exportAccessPdfBtn');

    function syncAccessSelectionUI() {
        const total = state.access.logs.length;
        const selected = state.access.selectedIds.size;

        document.getElementById('totalAccessSelectableCount').textContent = total;
        document.getElementById('selectedAccessBadge').textContent = `${selected} selected`;
        exportAccessPdfBtn.textContent = `Export Access Logs PDF (${selected})`;
        exportAccessPdfBtn.disabled = selected === 0;

        if (total > 0 && selected === total) {
            selectAllAccessCheckbox.checked = true;
            selectAllAccessCheckbox.indeterminate = false;
        } else if (selected > 0) {
            selectAllAccessCheckbox.checked = false;
            selectAllAccessCheckbox.indeterminate = true;
        } else {
            selectAllAccessCheckbox.checked = false;
            selectAllAccessCheckbox.indeterminate = false;
        }

        const pageBoxes = Array.from(accessTableBody.querySelectorAll('.access-row-checkbox'));
        if (pageBoxes.length > 0) {
            const checkedCount = pageBoxes.filter(cb => cb.checked).length;
            selectCurrentAccessPageCheckbox.checked = checkedCount === pageBoxes.length;
            selectCurrentAccessPageCheckbox.indeterminate = checkedCount > 0 && checkedCount < pageBoxes.length;
        } else {
            selectCurrentAccessPageCheckbox.checked = false;
            selectCurrentAccessPageCheckbox.indeterminate = false;
        }
    }

    function renderAccessPage() {
        const logs = state.access.logs;
        const totalPages = Math.max(1, Math.ceil(logs.length / PER_PAGE));
        if (state.access.page > totalPages) state.access.page = totalPages;

        window.renderTablePagination({
            containerId: 'access-logs-pagination',
            totalItems: logs.length,
            currentPage: state.access.page,
            perPage: PER_PAGE,
            onPageChange: (newPage) => {
                state.access.page = newPage;
                renderAccessPage();
            }
        });

        const colSpan = isExportMode ? 9 : 8;
        if (logs.length === 0) {
            accessTableBody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-5">No matching access log records found.</td></tr>`;
            syncAccessSelectionUI();
            return;
        }

        const start = (state.access.page - 1) * PER_PAGE;
        const slice = logs.slice(start, start + PER_PAGE);
        const selectColClass = isExportMode ? '' : 'd-none';

        accessTableBody.innerHTML = slice.map(row => {
            const id = Number(row.accesslogID);
            const checked = state.access.selectedIds.has(id) ? 'checked' : '';

            const statusBadge = row.status === 'success'
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle">Success</span>`
                : (row.status === 'failed'
                    ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Failed</span>`
                    : `<span class="badge bg-warning-subtle text-dark border border-warning-subtle">Warning</span>`);

            return `
                <tr>
                    <td class="px-3 text-center access-select-col ${selectColClass}">
                        <input class="form-check-input access-row-checkbox" type="checkbox" data-id="${id}" ${checked} style="cursor: pointer;">
                    </td>
                    <td class="px-3 text-muted small text-nowrap">${escapeHtml(row.formatted_date)}</td>
                    <td class="px-3">
                        <div class="fw-bold text-dark small">${escapeHtml(row.username ?? 'Guest / Unknown')}</div>
                        <small class="text-muted">${escapeHtml(row.email ?? 'N/A')}</small>
                    </td>
                    <td class="px-3"><span class="badge bg-light text-dark border text-capitalize">${escapeHtml(row.role ?? 'N/A')}</span></td>
                    <td class="px-3 fw-bold text-dark small">${escapeHtml(row.event)}</td>
                    <td class="px-3">${statusBadge}</td>
                    <td class="px-3 text-muted small text-nowrap">${escapeHtml(row.ip_address ?? 'N/A')}</td>
                    <td class="px-3 text-muted small" style="max-width: 240px;">
                        <div class="text-truncate">${escapeHtml(row.details ?? '-')}</div>
                    </td>
                    <td class="px-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary inspect-access-btn" data-id="${id}">View</button>
                    </td>
                </tr>
            `;
        }).join('');

        syncAccessSelectionUI();
    }

    async function fetchAccessLogs(resetPage = true) {
        if (resetPage) state.access.page = 1;

        const params = new URLSearchParams({
            search: accessSearch.value.trim(),
            status: accessStatusFilter.value,
            event: accessEventFilter.value,
            fromDate: accessFromDate.value,
            toDate: accessToDate.value,
        });

        try {
            const res = await fetch(`/audit-trail/access-logs?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                state.access.logs = data.logs;
                document.getElementById('tab-badge-access').textContent = data.summary.total;
                document.getElementById('access-sum-total').textContent = data.summary.total;
                document.getElementById('access-sum-success').textContent = data.summary.success;
                document.getElementById('access-sum-failed').textContent = data.summary.failed;
                document.getElementById('access-sum-warning').textContent = data.summary.warning;

                // Populate dynamic Event Type dropdown while preserving selection
                const prevEvent = accessEventFilter.value;
                accessEventFilter.innerHTML = '<option value="all">All Events</option>';
                data.events.forEach(ev => {
                    const opt = document.createElement('option');
                    opt.value = ev;
                    opt.textContent = ev;
                    accessEventFilter.appendChild(opt);
                });
                if ([...accessEventFilter.options].some(o => o.value === prevEvent)) {
                    accessEventFilter.value = prevEvent;
                }

                // Prune IDs no longer in filtered list
                const validIds = new Set(data.logs.map(r => Number(r.accesslogID)));
                state.access.selectedIds.forEach(id => {
                    if (!validIds.has(id)) state.access.selectedIds.delete(id);
                });

                renderAccessPage();
            }
        } catch (err) {
            accessTableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Failed to load access logs.</td></tr>`;
        }
    }

    selectAllAccessCheckbox.addEventListener('change', function () {
        if (this.checked) {
            state.access.logs.forEach(r => state.access.selectedIds.add(Number(r.accesslogID)));
        } else {
            state.access.selectedIds.clear();
        }
        renderAccessPage();
    });

    selectCurrentAccessPageCheckbox.addEventListener('change', function () {
        accessTableBody.querySelectorAll('.access-row-checkbox').forEach(cb => {
            const id = Number(cb.dataset.id);
            cb.checked = this.checked;
            this.checked ? state.access.selectedIds.add(id) : state.access.selectedIds.delete(id);
        });
        syncAccessSelectionUI();
    });

    document.getElementById('clearAccessSelectionBtn').addEventListener('click', () => {
        state.access.selectedIds.clear();
        renderAccessPage();
    });

    accessTableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('access-row-checkbox')) {
            const id = Number(e.target.dataset.id);
            e.target.checked ? state.access.selectedIds.add(id) : state.access.selectedIds.delete(id);
            syncAccessSelectionUI();
        }
    });

    accessTableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('inspect-access-btn')) {
            const id = Number(e.target.dataset.id);
            const row = state.access.logs.find(r => Number(r.accesslogID) === id);
            if (!row) return;

            detailModalTitle.textContent = `Access Log #${row.accesslogID} — ${row.event}`;
            detailModalBody.innerHTML = `
                <div class="row g-3 small">
                    <div class="col-md-6"><strong>Timestamp:</strong><br>${escapeHtml(row.formatted_date)}</div>
                    <div class="col-md-6"><strong>Status:</strong><br><span class="text-uppercase fw-bold">${escapeHtml(row.status)}</span></div>
                    <div class="col-md-6"><strong>Username:</strong><br>${escapeHtml(row.username ?? 'Guest / Unknown')}</div>
                    <div class="col-md-6"><strong>Email:</strong><br>${escapeHtml(row.email ?? 'N/A')}</div>
                    <div class="col-md-6"><strong>Role:</strong><br>${escapeHtml(row.role ?? 'N/A')}</div>
                    <div class="col-md-6"><strong>IP Address:</strong><br>${escapeHtml(row.ip_address ?? 'N/A')}</div>
                    <div class="col-12"><strong>Details:</strong><div class="p-2 bg-light border rounded mt-1">${escapeHtml(row.details ?? 'No additional details.')}</div></div>
                    <div class="col-12"><strong>User Agent / Device:</strong><div class="p-2 bg-light border rounded mt-1 text-break">${escapeHtml(row.user_agent ?? 'N/A')}</div></div>
                </div>
            `;
            detailModal.show();
        }
    });

    exportAccessPdfBtn.addEventListener('click', function () {
        const statusText = accessStatusFilter.options[accessStatusFilter.selectedIndex]?.text || 'All Status';
        const eventText = accessEventFilter.options[accessEventFilter.selectedIndex]?.text || 'All Events';
        let dateLabel = 'All Dates';
        if (accessFromDate.value && accessToDate.value) dateLabel = `${accessFromDate.value} to ${accessToDate.value}`;
        else if (accessFromDate.value) dateLabel = `From ${accessFromDate.value}`;
        else if (accessToDate.value) dateLabel = `Until ${accessToDate.value}`;

        submitPdfExport('/audit-trail/export-access-pdf', state.access.selectedIds, dateLabel, `${statusText} / ${eventText}`);
    });

    accessSearch.addEventListener('input', () => {
        clearTimeout(state.access.searchTimeout);
        state.access.searchTimeout = setTimeout(() => fetchAccessLogs(true), 300);
    });
    accessStatusFilter.addEventListener('change', () => fetchAccessLogs(true));
    accessEventFilter.addEventListener('change', () => fetchAccessLogs(true));
    accessFromDate.addEventListener('change', () => fetchAccessLogs(true));
    accessToDate.addEventListener('change', () => fetchAccessLogs(true));
    document.getElementById('resetAccessFilters').addEventListener('click', () => {
        accessSearch.value = '';
        accessStatusFilter.value = 'all';
        accessEventFilter.value = 'all';
        accessFromDate.value = '';
        accessToDate.value = '';
        fetchAccessLogs(true);
    });

    // ---------------------------------------------------------
    // TAB 2: ERROR LOGS LOGIC
    // ---------------------------------------------------------
    const errorSearch = document.getElementById('errorSearch');
    const errorLevelFilter = document.getElementById('errorLevelFilter');
    const errorFromDate = document.getElementById('errorFromDate');
    const errorToDate = document.getElementById('errorToDate');

    const selectAllErrorCheckbox = document.getElementById('selectAllErrorCheckbox');
    const selectCurrentErrorPageCheckbox = document.getElementById('selectCurrentErrorPageCheckbox');
    const exportErrorPdfBtn = document.getElementById('exportErrorPdfBtn');

    function syncErrorSelectionUI() {
        const total = state.error.logs.length;
        const selected = state.error.selectedIds.size;

        document.getElementById('totalErrorSelectableCount').textContent = total;
        document.getElementById('selectedErrorBadge').textContent = `${selected} selected`;
        exportErrorPdfBtn.textContent = `Export Error Logs PDF (${selected})`;
        exportErrorPdfBtn.disabled = selected === 0;

        if (total > 0 && selected === total) {
            selectAllErrorCheckbox.checked = true;
            selectAllErrorCheckbox.indeterminate = false;
        } else if (selected > 0) {
            selectAllErrorCheckbox.checked = false;
            selectAllErrorCheckbox.indeterminate = true;
        } else {
            selectAllErrorCheckbox.checked = false;
            selectAllErrorCheckbox.indeterminate = false;
        }

        const pageBoxes = Array.from(errorTableBody.querySelectorAll('.error-row-checkbox'));
        if (pageBoxes.length > 0) {
            const checkedCount = pageBoxes.filter(cb => cb.checked).length;
            selectCurrentErrorPageCheckbox.checked = checkedCount === pageBoxes.length;
            selectCurrentErrorPageCheckbox.indeterminate = checkedCount > 0 && checkedCount < pageBoxes.length;
        } else {
            selectCurrentErrorPageCheckbox.checked = false;
            selectCurrentErrorPageCheckbox.indeterminate = false;
        }
    }

    function renderErrorPage() {
        const logs = state.error.logs;
        const totalPages = Math.max(1, Math.ceil(logs.length / PER_PAGE));
        if (state.error.page > totalPages) state.error.page = totalPages;

        window.renderTablePagination({
            containerId: 'error-logs-pagination',
            totalItems: logs.length,
            currentPage: state.error.page,
            perPage: PER_PAGE,
            onPageChange: (newPage) => {
                state.error.page = newPage;
                renderErrorPage();
            }
        });

        const colSpan = isExportMode ? 7 : 6;
        if (logs.length === 0) {
            errorTableBody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center text-muted py-5">No matching error log records found.</td></tr>`;
            syncErrorSelectionUI();
            return;
        }

        const start = (state.error.page - 1) * PER_PAGE;
        const slice = logs.slice(start, start + PER_PAGE);
        const selectColClass = isExportMode ? '' : 'd-none';

        errorTableBody.innerHTML = slice.map(row => {
            const id = Number(row.errorlogID);
            const checked = state.error.selectedIds.has(id) ? 'checked' : '';

            const level = (row.level || 'error').toLowerCase();
            const levelBadge = ['critical', 'alert', 'emergency'].includes(level)
                ? `<span class="badge bg-dark text-uppercase">${escapeHtml(level)}</span>`
                : (level === 'warning'
                    ? `<span class="badge bg-warning-subtle text-dark border border-warning-subtle text-uppercase">${escapeHtml(level)}</span>`
                    : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle text-uppercase">${escapeHtml(level)}</span>`);

            const locationText = row.file ? `${row.file}:${row.line}` : (row.url || 'N/A');

            return `
                <tr>
                    <td class="px-3 text-center error-select-col ${selectColClass}">
                        <input class="form-check-input error-row-checkbox" type="checkbox" data-id="${id}" ${checked} style="cursor: pointer;">
                    </td>
                    <td class="px-3 text-muted small text-nowrap">${escapeHtml(row.formatted_date)}</td>
                    <td class="px-3">${levelBadge}</td>
                    <td class="px-3" style="max-width: 300px;">
                        <div class="fw-bold text-dark small text-truncate">${escapeHtml(row.message)}</div>
                        <small class="text-muted text-truncate d-block">${escapeHtml(row.exception ?? 'Application Log')}</small>
                    </td>
                    <td class="px-3 text-muted small" style="max-width: 220px;">
                        <div class="text-truncate">${escapeHtml(locationText)}</div>
                    </td>
                    <td class="px-3 text-muted small">${escapeHtml(row.username ?? 'System / Guest')}</td>
                    <td class="px-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary inspect-error-btn" data-id="${id}">View</button>
                    </td>
                </tr>
            `;
        }).join('');

        syncErrorSelectionUI();
    }

    async function fetchErrorLogs(resetPage = true) {
        if (resetPage) state.error.page = 1;

        const params = new URLSearchParams({
            search: errorSearch.value.trim(),
            level: errorLevelFilter.value,
            fromDate: errorFromDate.value,
            toDate: errorToDate.value,
        });

        try {
            const res = await fetch(`/audit-trail/error-logs?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                state.error.logs = data.logs;
                document.getElementById('tab-badge-error').textContent = data.summary.total;
                document.getElementById('error-sum-total').textContent = data.summary.total;
                document.getElementById('error-sum-critical').textContent = data.summary.critical;
                document.getElementById('error-sum-error').textContent = data.summary.error;
                document.getElementById('error-sum-warning').textContent = data.summary.warning;

                const validIds = new Set(data.logs.map(r => Number(r.errorlogID)));
                state.error.selectedIds.forEach(id => {
                    if (!validIds.has(id)) state.error.selectedIds.delete(id);
                });

                renderErrorPage();
            }
        } catch (err) {
            errorTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load error logs.</td></tr>`;
        }
    }

    selectAllErrorCheckbox.addEventListener('change', function () {
        if (this.checked) {
            state.error.logs.forEach(r => state.error.selectedIds.add(Number(r.errorlogID)));
        } else {
            state.error.selectedIds.clear();
        }
        renderErrorPage();
    });

    selectCurrentErrorPageCheckbox.addEventListener('change', function () {
        errorTableBody.querySelectorAll('.error-row-checkbox').forEach(cb => {
            const id = Number(cb.dataset.id);
            cb.checked = this.checked;
            this.checked ? state.error.selectedIds.add(id) : state.error.selectedIds.delete(id);
        });
        syncErrorSelectionUI();
    });

    document.getElementById('clearErrorSelectionBtn').addEventListener('click', () => {
        state.error.selectedIds.clear();
        renderErrorPage();
    });

    errorTableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('error-row-checkbox')) {
            const id = Number(e.target.dataset.id);
            e.target.checked ? state.error.selectedIds.add(id) : state.error.selectedIds.delete(id);
            syncErrorSelectionUI();
        }
    });

    errorTableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('inspect-error-btn')) {
            const id = Number(e.target.dataset.id);
            const row = state.error.logs.find(r => Number(r.errorlogID) === id);
            if (!row) return;

            detailModalTitle.textContent = `Error Log #${row.errorlogID} — ${String(row.level).toUpperCase()}`;
            detailModalBody.innerHTML = `
                <div class="row g-3 small">
                    <div class="col-md-6"><strong>Timestamp:</strong><br>${escapeHtml(row.formatted_date)}</div>
                    <div class="col-md-6"><strong>User / IP:</strong><br>${escapeHtml(row.username ?? 'System / Guest')} (${escapeHtml(row.ip_address ?? 'N/A')})</div>
                    <div class="col-md-6"><strong>Exception Class:</strong><br>${escapeHtml(row.exception ?? 'Application Log')}</div>
                    <div class="col-md-6"><strong>HTTP Request:</strong><br>${escapeHtml(row.method ?? 'N/A')} ${escapeHtml(row.url ?? 'N/A')}</div>
                    <div class="col-12"><strong>File &amp; Line:</strong><br><code>${escapeHtml(row.file ? `${row.file}:${row.line}` : 'N/A')}</code></div>
                    <div class="col-12"><strong>Error Message:</strong><div class="p-2 bg-danger-subtle text-danger border border-danger-subtle rounded mt-1 fw-bold">${escapeHtml(row.message)}</div></div>
                    <div class="col-12">
                        <strong>Stack Trace:</strong>
                        <pre class="p-3 bg-dark text-light rounded mt-1 small" style="max-height: 240px; overflow-y: auto; white-space: pre-wrap;">${escapeHtml(row.trace ?? 'No stack trace recorded for this entry.')}</pre>
                    </div>
                </div>
            `;
            detailModal.show();
        }
    });

    exportErrorPdfBtn.addEventListener('click', function () {
        const levelText = errorLevelFilter.options[errorLevelFilter.selectedIndex]?.text || 'All Levels';
        let dateLabel = 'All Dates';
        if (errorFromDate.value && errorToDate.value) dateLabel = `${errorFromDate.value} to ${errorToDate.value}`;
        else if (errorFromDate.value) dateLabel = `From ${errorFromDate.value}`;
        else if (errorToDate.value) dateLabel = `Until ${errorToDate.value}`;

        submitPdfExport('/audit-trail/export-error-pdf', state.error.selectedIds, dateLabel, levelText);
    });

    errorSearch.addEventListener('input', () => {
        clearTimeout(state.error.searchTimeout);
        state.error.searchTimeout = setTimeout(() => fetchErrorLogs(true), 300);
    });
    errorLevelFilter.addEventListener('change', () => fetchErrorLogs(true));
    errorFromDate.addEventListener('change', () => fetchErrorLogs(true));
    errorToDate.addEventListener('change', () => fetchErrorLogs(true));
    document.getElementById('resetErrorFilters').addEventListener('click', () => {
        errorSearch.value = '';
        errorLevelFilter.value = 'all';
        errorFromDate.value = '';
        errorToDate.value = '';
        fetchErrorLogs(true);
    });

    // Initial Load for Both Tables
    fetchAccessLogs(true);
    fetchErrorLogs(true);
});