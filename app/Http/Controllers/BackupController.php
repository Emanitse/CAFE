<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Account;
use Carbon\Carbon;
use Throwable;

class BackupController extends Controller
{
    private string $backupDir;

    // Core POS tables labeled for clarity
    private array $corePosTables = [
        'accounts'     => 'User Accounts & Roles',
        'categories'   => 'Menu Categories',
        'menuitems'    => 'Menu Items & Pricing',
        'orders'       => 'Customer Orders',
        'orderdetails' => 'Order Line Items',
        'payments'     => 'Payment Transactions',
    ];

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function index()
    {
        $stats = $this->gatherDatabaseStats();
        $savedBackups = $this->getSavedSnapshots();

        return view('components.backup', [
            'dbName'             => $stats['dbName'],
            'tables'             => $stats['tables'],
            'totalRows'          => $stats['totalRows'],
            'totalSizeFormatted' => $stats['totalSizeFormatted'],
            'savedBackups'       => $savedBackups,
        ]);
    }

    // Export full database or selected tables as a downloadable .sql file
    public function export(Request $request)
    {
        $mode = $request->input('mode', 'full');
        $availableTables = $this->getAllTableNames();

        if ($mode === 'selective') {
            $requestedTables = $request->input('tables', []);
            if (!is_array($requestedTables) || empty($requestedTables)) {
                return back()->with('error', 'Please select at least one table to export.');
            }

            // Whitelist against actual database tables to prevent SQL injection
            $selectedTables = array_values(array_intersect($availableTables, $requestedTables));
            if (empty($selectedTables)) {
                return back()->with('error', 'No valid database tables were selected.');
            }
        } else {
            $selectedTables = $availableTables;
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $prefix = $mode === 'selective' ? 'CafePOS_Partial_Backup_' : 'CafePOS_Full_Backup_';
        $filename = $prefix . $timestamp . '.sql';

        $sqlContent = $this->buildSqlDump($selectedTables, $mode);

        // Optionally save a server copy if requested
        if ($request->boolean('save_server_copy')) {
            File::put($this->backupDir . DIRECTORY_SEPARATOR . $filename, $sqlContent);
        }

        return response()->streamDownload(function () use ($sqlContent) {
            echo $sqlContent;
        }, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    // Create a full server snapshot via AJAX without reloading
    public function createSnapshot()
    {
        $allTables = $this->getAllTableNames();
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $filename = "CafePOS_Snapshot_{$timestamp}.sql";

        $sqlContent = $this->buildSqlDump($allTables, 'full');
        File::put($this->backupDir . DIRECTORY_SEPARATOR . $filename, $sqlContent);

        return response()->json([
            'success'      => true,
            'message'      => "Snapshot '{$filename}' created successfully.",
            'savedBackups' => $this->getSavedSnapshots(),
        ]);
    }

    // Download an existing server snapshot
    public function downloadSnapshot(string $filename)
    {
        $safeName = basename($filename);
        if (!str_ends_with(strtolower($safeName), '.sql')) {
            abort(404);
        }

        $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;
        if (!File::exists($fullPath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($fullPath, $safeName, [
            'Content-Type' => 'application/sql',
        ]);
    }

    // Delete an existing server snapshot
    public function deleteSnapshot(string $filename)
    {
        $safeName = basename($filename);
        if (!str_ends_with(strtolower($safeName), '.sql')) {
            return response()->json(['success' => false, 'message' => 'Invalid file name.'], 422);
        }

        $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        return response()->json([
            'success'      => true,
            'message'      => "Backup '{$safeName}' deleted.",
            'savedBackups' => $this->getSavedSnapshots(),
        ]);
    }

    // Restore database from uploaded .sql file OR existing server snapshot
    public function restore(Request $request)
    {
        $sqlContent = null;
        $sourceLabel = '';

        if ($request->filled('snapshot_filename')) {
            $safeName = basename($request->input('snapshot_filename'));
            $fullPath = $this->backupDir . DIRECTORY_SEPARATOR . $safeName;

            if (!str_ends_with(strtolower($safeName), '.sql') || !File::exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected server snapshot could not be found.',
                ], 404);
            }

            $sqlContent = File::get($fullPath);
            $sourceLabel = $safeName;
        } elseif ($request->hasFile('sql_file')) {
            $file = $request->file('sql_file');

            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file failed to transfer. Please try again.',
                ], 422);
            }

            if (strtolower($file->getClientOriginalExtension()) !== 'sql') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Only .sql backup files are allowed.',
                ], 422);
            }

            // Max 50MB safeguard
            if ($file->getSize() > 50 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL file exceeds the 50MB maximum limit.',
                ], 422);
            }

            $sqlContent = File::get($file->getRealPath());
            $sourceLabel = $file->getClientOriginalName();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please upload a .sql file or choose a saved restore point.',
            ], 422);
        }

        if (trim((string) $sqlContent) === '' || str_contains($sqlContent, "\0")) {
            return response()->json([
                'success' => false,
                'message' => 'The selected file is empty or not a valid text-based SQL dump.',
            ], 422);
        }

        $statements = $this->splitSqlStatements($sqlContent);
        if (empty($statements)) {
            return response()->json([
                'success' => false,
                'message' => 'No executable SQL statements were found in this file.',
            ], 422);
        }

        // Create automatic safety backup before restoring
        $safetyFilename = null;
        if ($request->boolean('auto_backup', true)) {
            $safetyFilename = 'PreRestore_Safety_' . Carbon::now()->format('Y-m-d_His') . '.sql';
            $safetyDump = $this->buildSqlDump($this->getAllTableNames(), 'full');
            File::put($this->backupDir . DIRECTORY_SEPARATOR . $safetyFilename, $safetyDump);
        }

        $pdo = DB::connection()->getPdo();
        $executedCount = 0;

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($statements as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') {
                    continue;
                }
                $pdo->exec($trimmed);
                $executedCount++;
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restore failed at statement #' . ($executedCount + 1) . ': ' . $e->getMessage(),
            ], 500);
        } finally {
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
            } catch (Throwable $ignored) {
            }
        }

        // Keep the current admin logged in after accounts table restoration
        if (session()->has('accountID')) {
            $currentAccount = Account::withoutGlobalScope('active')->find(session('accountID'));
            if ($currentAccount) {
                session([
                    'username' => $currentAccount->username,
                    'email'    => $currentAccount->email,
                    'role'     => $currentAccount->role,
                ]);
            }
        }

        $updatedStats = $this->gatherDatabaseStats();

        return response()->json([
            'success'      => true,
            'message'      => "Database restored successfully from '{$sourceLabel}' ({$executedCount} statements executed).",
            'safetyBackup' => $safetyFilename,
            'stats'        => $updatedStats,
            'savedBackups' => $this->getSavedSnapshots(),
        ]);
    }

    // Build pure-PHP MySQL/MariaDB SQL dump
    private function buildSqlDump(array $tables, string $mode): string
    {
        $pdo = DB::connection()->getPdo();
        $dbName = DB::getDatabaseName();
        $generatedAt = Carbon::now()->format('Y-m-d H:i:s');
        $generatedBy = session('username') ?? 'Administrator';

        $lines = [
            "-- ==========================================================",
            "-- Cafe Ordering System - Database SQL Backup",
            "-- Database: `{$dbName}`",
            "-- Export Mode: " . strtoupper($mode) . " (" . count($tables) . " table(s))",
            "-- Generated At: {$generatedAt}",
            "-- Generated By: {$generatedBy}",
            "-- ==========================================================",
            "",
            "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";",
            "SET FOREIGN_KEY_CHECKS = 0;",
            "SET time_zone = \"+00:00\";",
            "",
        ];

        foreach ($tables as $table) {
            $safeTable = str_replace('`', '``', $table);

            // Table Structure (DDL)
            $createRow = DB::selectOne("SHOW CREATE TABLE `{$safeTable}`");
            $createArr = (array) $createRow;
            $createSql = $createArr['Create Table'] ?? array_values($createArr)[1] ?? '';

            $lines[] = "-- ----------------------------------------------------------";
            $lines[] = "-- Table structure for `{$safeTable}`";
            $lines[] = "-- ----------------------------------------------------------";
            $lines[] = "DROP TABLE IF EXISTS `{$safeTable}`;";
            $lines[] = $createSql . ";";
            $lines[] = "";

            // Table Data (DML)
            $rows = DB::table($table)->get();
            if ($rows->isNotEmpty()) {
                $lines[] = "-- Dumping data for table `{$safeTable}` ({$rows->count()} rows)";

                $firstRow = (array) $rows->first();
                $columns = array_map(fn($col) => '`' . str_replace('`', '``', $col) . '`', array_keys($firstRow));
                $columnList = implode(', ', $columns);

                // Chunk inserts in batches of 100 rows for clean, fast imports
                foreach ($rows->chunk(100) as $chunk) {
                    $valueGroups = [];
                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ((array) $row as $val) {
                            if ($val === null) {
                                $vals[] = 'NULL';
                            } elseif (is_int($val) || is_float($val)) {
                                $vals[] = (string) $val;
                            } else {
                                $vals[] = $pdo->quote((string) $val);
                            }
                        }
                        $valueGroups[] = '(' . implode(', ', $vals) . ')';
                    }

                    $lines[] = "INSERT INTO `{$safeTable}` ({$columnList}) VALUES\n" . implode(",\n", $valueGroups) . ";";
                }

                $lines[] = "";
            }
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS = 1;";
        $lines[] = "-- End of Cafe Ordering System Backup";

        return implode("\n", $lines) . "\n";
    }

    // Character-safe SQL statement splitter respecting quotes & comments
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);

        $inSingleQuote = false;
        $inDoubleQuote = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                // Start of -- or # comment
                if (($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) || $char === '#') {
                    $inLineComment = true;
                    continue;
                }
                // Start of /* */ comment (excluding MySQL conditional /*! ... */ directives)
                if ($char === '/' && $next === '*' && ($i + 2 >= $length || $sql[$i + 2] !== '!')) {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($char === "'" && !$inDoubleQuote && !$inBacktick) {
                if ($inSingleQuote && $next === "'") {
                    $buffer .= "''";
                    $i++;
                    continue;
                }
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote && !$inBacktick) {
                $inDoubleQuote = !$inDoubleQuote;
            } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
                $inBacktick = !$inBacktick;
            } elseif ($char === '\\' && ($inSingleQuote || $inDoubleQuote) && $i + 1 < $length) {
                $buffer .= $char . $next;
                $i++;
                continue;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
            } else {
                $buffer .= $char;
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }

    private function getAllTableNames(): array
    {
        $dbName = DB::getDatabaseName();
        $key = "Tables_in_{$dbName}";
        $raw = DB::select('SHOW TABLES');

        return array_map(function ($row) use ($key) {
            $arr = (array) $row;
            return $arr[$key] ?? array_values($arr)[0];
        }, $raw);
    }

    private function gatherDatabaseStats(): array
    {
        $dbName = DB::getDatabaseName();
        $statuses = DB::select('SHOW TABLE STATUS');

        $tables = [];
        $totalRows = 0;
        $totalBytes = 0;

        foreach ($statuses as $status) {
            $arr = (array) $status;
            $name = $arr['Name'];
            $exactCount = (int) DB::table($name)->count();
            $bytes = (int) ($arr['Data_length'] ?? 0) + (int) ($arr['Index_length'] ?? 0);

            $totalRows += $exactCount;
            $totalBytes += $bytes;

            $tables[] = [
                'name'        => $name,
                'label'       => $this->corePosTables[$name] ?? 'System / Framework Table',
                'is_core_pos' => array_key_exists($name, $this->corePosTables),
                'rows'        => $exactCount,
                'size'        => $this->formatBytes($bytes),
            ];
        }

        // Sort core POS tables first
        usort($tables, function ($a, $b) {
            if ($a['is_core_pos'] !== $b['is_core_pos']) {
                return $b['is_core_pos'] <=> $a['is_core_pos'];
            }
            return strcmp($a['name'], $b['name']);
        });

        return [
            'dbName'             => $dbName,
            'tables'             => $tables,
            'totalTables'        => count($tables),
            'totalRows'          => $totalRows,
            'totalSizeFormatted' => $this->formatBytes($totalBytes),
        ];
    }

    private function getSavedSnapshots(): array
    {
        if (!File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $list = [];

        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'sql') {
                continue;
            }

            $name = $file->getFilename();
            $type = str_starts_with($name, 'PreRestore_')
                ? 'Safety Backup'
                : (str_contains($name, 'Partial') ? 'Selective Export' : 'Full Snapshot');

            $list[] = [
                'filename'   => $name,
                'type'       => $type,
                'size'       => $this->formatBytes($file->getSize()),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('M d, Y h:i A'),
                'timestamp'  => $file->getMTime(),
            ];
        }

        usort($list, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $list;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}