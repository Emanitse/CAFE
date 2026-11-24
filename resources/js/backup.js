document.addEventListener('DOMContentLoaded', function () {
    const exportForm = document.getElementById('exportSqlForm');
    if (!exportForm) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const globalAlert = document.getElementById('backupGlobalAlert');

    // Export Elements
    const modeFullRadio = document.getElementById('exportModeFull');
    const modeSelectiveRadio = document.getElementById('exportModeSelective');
    const selectiveSection = document.getElementById('selectiveTableSection');
    const tableCheckboxes = Array.from(document.querySelectorAll('.export-table-checkbox'));
    const selectedTableCount = document.getElementById('selectedTableCount');
    const downloadSqlBtn = document.getElementById('downloadSqlBtn');

    // Restore Elements
    const sqlDropZone = document.getElementById('sqlDropZone');
    const restoreSqlInput = document.getElementById('restoreSqlInput');
    const sqlFilePreviewCard = document.getElementById('sqlFilePreviewCard');
    const previewFileName = document.getElementById('previewFileName');
    const previewFileMeta = document.getElementById('previewFileMeta');
    const previewDetectedTables = document.getElementById('previewDetectedTables');
    const clearSqlFileBtn = document.getElementById('clearSqlFileBtn');
    const openRestoreConfirmBtn = document.getElementById('openRestoreConfirmBtn');
    const autoSafetyBackupCheck = document.getElementById('autoSafetyBackupCheck');

    // Modal & Snapshots Elements
    const confirmRestoreModal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    const modalRestoreSourceName = document.getElementById('modalRestoreSourceName');
    const restoreConfirmKeyword = document.getElementById('restoreConfirmKeyword');
    const executeRestoreBtn = document.getElementById('executeRestoreBtn');
    const snapshotsTableBody = document.getElementById('snapshots-table-body');
    const snapshotCountBadge = document.getElementById('snapshotCountBadge');
    const createSnapshotBtn = document.getElementById('createSnapshotBtn');

    let pendingSnapshotFilename = null;

    function showAlert(message, type = 'success') {
        globalAlert.className = `alert alert-${type} py-2 small mb-3`;
        globalAlert.textContent = message;
        globalAlert.classList.remove('d-none');
        globalAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    // ---------------------------------------------------------
    // 1. EXPORT MODE & SELECTIVE TABLE PICKER
    // ---------------------------------------------------------
    function updateExportUI() {
        const isSelective = modeSelectiveRadio.checked;
        selectiveSection.classList.toggle('d-none', !isSelective);

        const checkedCount = tableCheckboxes.filter(cb => cb.checked).length;
        selectedTableCount.textContent = checkedCount;

        if (isSelective && checkedCount === 0) {
            downloadSqlBtn.disabled = true;
            downloadSqlBtn.textContent = 'SELECT AT LEAST 1 TABLE';
        } else {
            downloadSqlBtn.disabled = false;
            downloadSqlBtn.textContent = isSelective
                ? `DOWNLOAD SELECTED TABLES (${checkedCount}) .SQL`
                : 'DOWNLOAD FULL DATABASE .SQL';
        }
    }

    modeFullRadio.addEventListener('change', updateExportUI);
    modeSelectiveRadio.addEventListener('change', updateExportUI);
    tableCheckboxes.forEach(cb => cb.addEventListener('change', updateExportUI));

    document.getElementById('selectCorePosTablesBtn')?.addEventListener('click', () => {
        tableCheckboxes.forEach(cb => cb.checked = cb.dataset.core === '1');
        updateExportUI();
    });

    document.getElementById('selectAllTablesBtn')?.addEventListener('click', () => {
        tableCheckboxes.forEach(cb => cb.checked = true);
        updateExportUI();
    });

    document.getElementById('clearAllTablesBtn')?.addEventListener('click', () => {
        tableCheckboxes.forEach(cb => cb.checked = false);
        updateExportUI();
    });

    // Refresh server snapshot list shortly after a download if "save_server_copy" was checked
    exportForm.addEventListener('submit', function () {
        if (document.getElementById('saveServerCopyCheck')?.checked) {
            setTimeout(() => {
                fetch('/backup', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const freshBody = doc.getElementById('snapshots-table-body');
                        const freshBadge = doc.getElementById('snapshotCountBadge');
                        if (freshBody && snapshotsTableBody) snapshotsTableBody.innerHTML = freshBody.innerHTML;
                        if (freshBadge && snapshotCountBadge) snapshotCountBadge.textContent = freshBadge.textContent;
                    })
                    .catch(() => {});
            }, 1500);
        }
    });

    // ---------------------------------------------------------
    // 2. SQL FILE UPLOAD & CLIENT-SIDE INSPECTION
    // ---------------------------------------------------------
    function inspectSqlFile(file) {
        if (!file) return;

        if (!file.name.toLowerCase().endsWith('.sql')) {
            restoreSqlInput.value = '';
            return showAlert('Only .sql backup files are supported.', 'danger');
        }

        if (file.size > 50 * 1024 * 1024) {
            restoreSqlInput.value = '';
            return showAlert('File size exceeds the 50MB maximum limit.', 'danger');
        }

        pendingSnapshotFilename = null;
        previewFileName.textContent = file.name;
        previewFileMeta.textContent = `Size: ${formatSize(file.size)}`;
        previewDetectedTables.textContent = 'Scanning SQL tables...';
        sqlFilePreviewCard.classList.remove('d-none');
        openRestoreConfirmBtn.disabled = false;

        // Read first 512KB to detect CREATE TABLE / INSERT INTO table names
        const reader = new FileReader();
        reader.onload = function (e) {
            const text = e.target.result || '';
            const matches = [...text.matchAll(/(?:CREATE TABLE(?:\s+IF NOT EXISTS)?|INSERT INTO)\s+`?([a-zA-Z0-9_]+)`?/gi)];
            const uniqueTables = [...new Set(matches.map(m => m[1]))];

            previewDetectedTables.textContent = uniqueTables.length > 0
                ? uniqueTables.join(', ')
                : 'Custom SQL statements';
        };
        reader.readAsText(file.slice(0, 512 * 1024));
    }

    sqlDropZone.addEventListener('click', () => restoreSqlInput.click());

    restoreSqlInput.addEventListener('change', function () {
        if (this.files[0]) inspectSqlFile(this.files[0]);
    });

    sqlDropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        this.classList.add('border-dark');
    });

    sqlDropZone.addEventListener('dragleave', function () {
        this.classList.remove('border-dark');
    });

    sqlDropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('border-dark');
        if (e.dataTransfer.files[0]) {
            restoreSqlInput.files = e.dataTransfer.files;
            inspectSqlFile(e.dataTransfer.files[0]);
        }
    });

    clearSqlFileBtn.addEventListener('click', function () {
        restoreSqlInput.value = '';
        sqlFilePreviewCard.classList.add('d-none');
        openRestoreConfirmBtn.disabled = true;
    });

    // ---------------------------------------------------------
    // 3. SERVER SNAPSHOTS (CREATE, RESTORE, DELETE)
    // ---------------------------------------------------------
    function renderSnapshotsTable(snapshots) {
        snapshotCountBadge.textContent = `${snapshots.length} file(s)`;

        if (snapshots.length === 0) {
            snapshotsTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        No server snapshots saved yet. Click "+ Save Server Snapshot" or export a backup to create one.
                    </td>
                </tr>
            `;
            return;
        }

        snapshotsTableBody.innerHTML = snapshots.map(snap => {
            const badgeClass = snap.type === 'Safety Backup'
                ? 'bg-warning text-dark'
                : 'bg-light text-dark border';

            return `
                <tr>
                    <td class="px-4 fw-bold text-dark">${snap.filename}</td>
                    <td class="px-4"><span class="badge ${badgeClass}">${snap.type}</span></td>
                    <td class="px-4 text-muted small">${snap.size}</td>
                    <td class="px-4 text-muted small">${snap.created_at}</td>
                    <td class="px-4 text-end text-nowrap">
                        <a href="/backup/snapshot/${encodeURIComponent(snap.filename)}/download" class="btn btn-sm btn-outline-secondary me-1">
                            Download
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-dark restore-snapshot-btn me-1" data-filename="${snap.filename}">
                            Restore
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-snapshot-btn" data-filename="${snap.filename}">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    createSnapshotBtn?.addEventListener('click', async function () {
        this.disabled = true;
        this.textContent = 'Saving Snapshot...';

        try {
            const res = await fetch('/backup/snapshot', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            });
            const data = await res.json();

            if (data.success) {
                showAlert(data.message, 'success');
                renderSnapshotsTable(data.savedBackups);
            } else {
                showAlert(data.message || 'Failed to create snapshot.', 'danger');
            }
        } catch (err) {
            showAlert('Error creating server snapshot.', 'danger');
        } finally {
            this.disabled = false;
            this.textContent = '+ Save Server Snapshot';
        }
    });

    snapshotsTableBody.addEventListener('click', async function (e) {
        // Restore from server snapshot
        if (e.target.classList.contains('restore-snapshot-btn')) {
            pendingSnapshotFilename = e.target.dataset.filename;
            modalRestoreSourceName.textContent = `Server Snapshot: ${pendingSnapshotFilename}`;
            restoreConfirmKeyword.value = '';
            executeRestoreBtn.disabled = true;
            confirmRestoreModal.show();
        }

        // Delete server snapshot
        if (e.target.classList.contains('delete-snapshot-btn')) {
            const filename = e.target.dataset.filename;
            if (!confirm(`Delete saved backup '${filename}'?`)) return;

            e.target.disabled = true;
            try {
                const res = await fetch(`/backup/snapshot/${encodeURIComponent(filename)}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                });
                const data = await res.json();
                if (data.success) {
                    showAlert(data.message, 'secondary');
                    renderSnapshotsTable(data.savedBackups);
                }
            } catch (err) {
                showAlert('Failed to delete snapshot.', 'danger');
            }
        }
    });

    // ---------------------------------------------------------
    // 4. CONFIRM & EXECUTE DATABASE RESTORE
    // ---------------------------------------------------------
    openRestoreConfirmBtn.addEventListener('click', function () {
        const file = restoreSqlInput.files[0];
        if (!file) return;

        pendingSnapshotFilename = null;
        modalRestoreSourceName.textContent = `Uploaded File: ${file.name} (${formatSize(file.size)})`;
        restoreConfirmKeyword.value = '';
        executeRestoreBtn.disabled = true;
        confirmRestoreModal.show();
    });

    restoreConfirmKeyword.addEventListener('input', function () {
        executeRestoreBtn.disabled = this.value.trim().toUpperCase() !== 'RESTORE';
    });

    executeRestoreBtn.addEventListener('click', async function () {
        const formData = new FormData();
        formData.append('auto_backup', autoSafetyBackupCheck.checked ? '1' : '0');

        if (pendingSnapshotFilename) {
            formData.append('snapshot_filename', pendingSnapshotFilename);
        } else if (restoreSqlInput.files[0]) {
            formData.append('sql_file', restoreSqlInput.files[0]);
        } else {
            return;
        }

        this.disabled = true;
        this.textContent = 'Restoring...';

        try {
            const res = await fetch('/backup/restore', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await res.json();
            confirmRestoreModal.hide();

            if (data.success) {
                let msg = data.message;
                if (data.safetyBackup) {
                    msg += ` Safety backup saved as '${data.safetyBackup}'.`;
                }
                showAlert(msg, 'success');

                // Reset upload state
                restoreSqlInput.value = '';
                sqlFilePreviewCard.classList.add('d-none');
                openRestoreConfirmBtn.disabled = true;
                pendingSnapshotFilename = null;

                // Refresh KPI stats & snapshots list live
                if (data.stats) {
                    document.getElementById('stat-total-tables').textContent = data.stats.totalTables;
                    document.getElementById('stat-total-rows').textContent = Number(data.stats.totalRows).toLocaleString();
                    document.getElementById('stat-total-size').textContent = data.stats.totalSizeFormatted;
                }
                if (data.savedBackups) {
                    renderSnapshotsTable(data.savedBackups);
                }
            } else {
                showAlert(data.message || 'Database restore failed.', 'danger');
            }
        } catch (err) {
            confirmRestoreModal.hide();
            showAlert('An unexpected error occurred during database restore.', 'danger');
        } finally {
            this.disabled = false;
            this.textContent = 'Execute Restore';
        }
    });

    updateExportUI();
});