@extends('layouts.app')

@section('content')
<div class="container-fluid pb-4">

    <!-- Header & Quick Server Snapshot -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">System Backup &amp; Restore</h3>
            <small class="text-muted">Export database SQL dumps, manage restore points, and recover system data</small>
        </div>

        <button type="button" id="createSnapshotBtn" class="btn bg-coffee text-white fw-bold shadow-sm px-4 py-2">
            + Save Server Snapshot
        </button>
    </div>

    <!-- Global Status Alert -->
    <div id="backupGlobalAlert" class="alert d-none py-2 small mb-3" role="alert"></div>

    @if(session('error'))
        <div class="alert alert-danger py-2 small mb-3">{{ session('error') }}</div>
    @endif

    <!-- Top Database Health KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Active Database</small>
                    <h5 class="fw-bold text-dark mb-0 mt-1 text-truncate" id="stat-db-name">{{ $dbName }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Total Tables</small>
                    <h5 class="fw-bold text-dark mb-0 mt-1" id="stat-total-tables">{{ count($tables) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted fw-bold text-uppercase" style="font-size: 0.72rem;">Total Records</small>
                    <h5 class="fw-bold text-success mb-0 mt-1" id="stat-total-rows">{{ number_format($totalRows) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 bg-coffee text-white">
                <div class="card-body">
                    <small class="opacity-75 fw-bold text-uppercase" style="font-size: 0.72rem;">Database Size</small>
                    <h5 class="fw-bold mb-0 mt-1" id="stat-total-size">{{ $totalSizeFormatted }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Main 2-Column Workspace: Left (Export SQL) | Right (Restore SQL) -->
    <div class="row g-4 mb-4">

        <!-- LEFT: EXPORT / DOWNLOAD SQL BACKUP -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Export Database (.SQL)</h6>
                        <small class="text-muted">Download the entire database or pick specific tables</small>
                    </div>
                    <span class="badge bg-light text-dark border">MySQL / MariaDB</span>
                </div>

                <div class="card-body p-4 d-flex flex-column">
                    <form id="exportSqlForm" action="{{ route('backup.export') }}" method="POST" class="d-flex flex-column flex-grow-1">
                        @csrf

                        <!-- Export Mode Selector -->
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <input type="radio" class="btn-check" name="mode" id="exportModeFull" value="full" autocomplete="off" checked>
                                <label class="btn btn-outline-dark w-100 text-start p-3 h-100" for="exportModeFull">
                                    <div class="fw-bold">Full Database Backup</div>
                                    <small class="text-muted d-block">Exports all tables, structures, and records</small>
                                </label>
                            </div>
                            <div class="col-12 col-sm-6">
                                <input type="radio" class="btn-check" name="mode" id="exportModeSelective" value="selective" autocomplete="off">
                                <label class="btn btn-outline-dark w-100 text-start p-3 h-100" for="exportModeSelective">
                                    <div class="fw-bold">Selective Table Export</div>
                                    <small class="text-muted d-block">Choose specific tables to include in the .sql</small>
                                </label>
                            </div>
                        </div>

                        <!-- Selective Table Picker -->
                        <div id="selectiveTableSection" class="border rounded bg-light p-3 mb-3 d-none">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-2 border-bottom">
                                <span class="fw-bold small text-dark">
                                    Select Tables (<span id="selectedTableCount">0</span> selected)
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" id="selectCorePosTablesBtn" class="btn btn-sm btn-outline-dark py-0 px-2" style="font-size: 0.75rem;">
                                        Core POS Only
                                    </button>
                                    <button type="button" id="selectAllTablesBtn" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">
                                        Select All
                                    </button>
                                    <button type="button" id="clearAllTablesBtn" class="btn btn-sm btn-link text-danger text-decoration-none py-0 px-1" style="font-size: 0.75rem;">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 230px;">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody id="export-tables-list">
                                        @foreach($tables as $tbl)
                                            <tr class="border-bottom">
                                                <td style="width: 32px;">
                                                    <input class="form-check-input export-table-checkbox"
                                                           type="checkbox"
                                                           name="tables[]"
                                                           value="{{ $tbl['name'] }}"
                                                           id="tbl_{{ $tbl['name'] }}"
                                                           data-core="{{ $tbl['is_core_pos'] ? '1' : '0' }}"
                                                           {{ $tbl['is_core_pos'] ? 'checked' : '' }}
                                                           style="cursor: pointer;">
                                                </td>
                                                <td>
                                                    <label class="fw-bold text-dark small mb-0" for="tbl_{{ $tbl['name'] }}" style="cursor: pointer;">
                                                        {{ $tbl['name'] }}
                                                    </label>
                                                    <span class="text-muted d-block" style="font-size: 0.72rem;">{{ $tbl['label'] }}</span>
                                                </td>
                                                <td class="text-end">
                                                    @if($tbl['is_core_pos'])
                                                        <span class="badge bg-coffee" style="font-size: 0.65rem;">POS</span>
                                                    @endif
                                                </td>
                                                <td class="text-end text-muted small text-nowrap">
                                                    <span class="fw-bold text-dark">{{ number_format($tbl['rows']) }}</span> rows
                                                    <span class="text-muted">({{ $tbl['size'] }})</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Also keep a server copy toggle -->
                        <div class="form-check mb-4 mt-auto">
                            <input class="form-check-input" type="checkbox" name="save_server_copy" value="1" id="saveServerCopyCheck" checked style="cursor: pointer;">
                            <label class="form-check-label small text-muted" for="saveServerCopyCheck" style="cursor: pointer;">
                                Also keep a copy in Server Restore Points list
                            </label>
                        </div>

                        <button type="submit" id="downloadSqlBtn" class="btn bg-coffee text-white fw-bold w-100 py-2 shadow-sm">
                            DOWNLOAD .SQL BACKUP
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: RESTORE FROM UPLOADED .SQL FILE -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Restore from .SQL File</h6>
                        <small class="text-muted">Upload a saved backup file from your device</small>
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Admin Only</span>
                </div>

                <div class="card-body p-4 d-flex flex-column">
                    <!-- Drag & Drop / File Picker Zone -->
                    <div id="sqlDropZone" class="border border-2 border-dashed rounded bg-light p-4 text-center mb-3" style="cursor: pointer;">
                        <input type="file" id="restoreSqlInput" accept=".sql" class="d-none">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#66402E" stroke-width="2" class="mb-2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <div class="fw-bold text-dark">Click to choose or drag &amp; drop a .sql file</div>
                        <small class="text-muted d-block">Maximum file size: 50MB</small>
                    </div>

                    <!-- Uploaded File Inspection Preview -->
                    <div id="sqlFilePreviewCard" class="border rounded bg-white p-3 mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark small text-truncate" id="previewFileName">backup.sql</div>
                                <small class="text-muted" id="previewFileMeta">0 KB</small>
                            </div>
                            <button type="button" id="clearSqlFileBtn" class="btn btn-sm btn-outline-danger py-0 px-2">
                                Remove
                            </button>
                        </div>
                        <div class="small text-muted">
                            <strong>Detected Tables:</strong>
                            <span id="previewDetectedTables" class="text-dark">Analyzing...</span>
                        </div>
                    </div>

                    <!-- Automatic Pre-Restore Safety Snapshot Option -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="autoSafetyBackupCheck" checked style="cursor: pointer;">
                        <label class="form-check-label small text-dark fw-bold" for="autoSafetyBackupCheck" style="cursor: pointer;">
                            Create automatic safety backup before restoring
                        </label>
                        <small class="text-muted d-block">Recommended: saves current data so you can undo this restore if needed.</small>
                    </div>

                    <div class="alert alert-warning py-2 small mb-4 mt-auto">
                        <strong>Warning:</strong> Restoring an <code>.sql</code> file will overwrite existing tables and records included in the backup file.
                    </div>

                    <button type="button" id="openRestoreConfirmBtn" class="btn btn-danger fw-bold w-100 py-2 shadow-sm" disabled>
                        RESTORE DATABASE
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM: SERVER RESTORE POINTS TABLE -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-0">Saved Server Restore Points</h6>
                <small class="text-muted">Quick-access .sql snapshots stored on the server</small>
            </div>
            <span class="badge bg-coffee" id="snapshotCountBadge">{{ count($savedBackups) }} file(s)</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3 text-muted">Backup Filename</th>
                            <th class="px-4 py-3 text-muted">Type</th>
                            <th class="px-4 py-3 text-muted">File Size</th>
                            <th class="px-4 py-3 text-muted">Created At</th>
                            <th class="px-4 py-3 text-muted text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="snapshots-table-body">
                        @forelse($savedBackups as $snap)
                            <tr>
                                <td class="px-4 fw-bold text-dark">{{ $snap['filename'] }}</td>
                                <td class="px-4">
                                    <span class="badge {{ $snap['type'] === 'Safety Backup' ? 'bg-warning text-dark' : 'bg-light text-dark border' }}">
                                        {{ $snap['type'] }}
                                    </span>
                                </td>
                                <td class="px-4 text-muted small">{{ $snap['size'] }}</td>
                                <td class="px-4 text-muted small">{{ $snap['created_at'] }}</td>
                                <td class="px-4 text-end text-nowrap">
                                    <a href="{{ route('backup.downloadSnapshot', $snap['filename']) }}" class="btn btn-sm btn-outline-secondary me-1">
                                        Download
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-dark restore-snapshot-btn me-1" data-filename="{{ $snap['filename'] }}">
                                        Restore
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-snapshot-btn" data-filename="{{ $snap['filename'] }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No server snapshots saved yet. Click "+ Save Server Snapshot" or export a backup to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- RESTORE CONFIRMATION MODAL -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold">Confirm Database Restore</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark mb-2">
                    You are about to restore the database using:
                </p>
                <div class="p-2 bg-light border rounded fw-bold text-dark small mb-3" id="modalRestoreSourceName">
                    backup.sql
                </div>
                <p class="text-muted small mb-3">
                    Existing records in the restored tables will be replaced. Type <strong class="text-danger">RESTORE</strong> below to confirm:
                </p>
                <input type="text" id="restoreConfirmKeyword" class="form-control bg-light mb-4" placeholder="Type RESTORE to proceed" autocomplete="off">

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="executeRestoreBtn" class="btn btn-danger fw-bold px-4" disabled>
                        Execute Restore
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection