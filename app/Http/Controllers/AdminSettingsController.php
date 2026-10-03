<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\StoreSettingsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSettingsController extends Controller
{
    public function edit(): Response
    {
        $logPath = storage_path('logs/laravel.log');
        $logInfo = [
            'exists' => file_exists($logPath),
            'size' => file_exists($logPath) ? self::formatBytes(filesize($logPath)) : '0 B',
            'raw_bytes' => file_exists($logPath) ? filesize($logPath) : 0,
            'updated_at' => file_exists($logPath) ? date('Y-m-d H:i:s', filemtime($logPath)) : '—',
        ];

        $storageLinked = file_exists(public_path('storage'));
        $catalogPath = storage_path('app/public/catalog');
        $catalogFilesCount = file_exists($catalogPath) ? count(glob($catalogPath.'/*') ?: []) : 0;

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? (PHP_OS_FAMILY.' ('.php_uname('s').')'),
            'opcache_active' => function_exists('opcache_get_status') && is_array(@opcache_get_status(false)) && ! empty(opcache_get_status(false)['opcache_enabled']),
            'storage_linked' => $storageLinked,
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'client_ip' => request()->ip() ?? '127.0.0.1',
            'memory_limit' => ini_get('memory_limit') ?: '256M',
            'max_execution_time' => ini_get('max_execution_time') ? ini_get('max_execution_time').'s' : '—',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: '—',
            'post_max_size' => ini_get('post_max_size') ?: '—',
            'disk_free_space' => function_exists('disk_free_space') ? self::formatBytes(@disk_free_space(base_path()) ?: 0) : '—',
            'failed_jobs_count' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'catalog_files_count' => $catalogFilesCount,
        ];

        return Inertia::render('admin/SettingsPage', [
            'settings' => Setting::firstOrFail(),
            'systemInfo' => $systemInfo,
            'logInfo' => $logInfo,
            'databaseTables' => $this->getDatabaseTables(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Identitas & Jam
            'store_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:400'],
            'business_hours' => ['nullable', 'string', 'max:120'],
            'origin_city' => ['nullable', 'string', 'max:120'],
            'origin_destination_id' => ['nullable', 'string', 'max:50'],

            // Kontak & Sosial Media
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:30'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'tiktok_url' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'string', 'max:1000'],

            // Aturan Penjualan & Pengiriman
            'free_shipping_from' => ['nullable', 'integer', 'min:0'],
            'free_shipping_cities' => ['nullable', 'string', 'max:500'],
            'bulk_minimum' => ['nullable', 'integer', 'min:0'],
            'handling_time' => ['nullable', 'string', 'max:100'],
            'shipping_note' => ['nullable', 'string', 'max:500'],

            // Operasional, Maintenance & Status
            'store_status' => ['nullable', 'string', 'in:open,holiday,maintenance'],
            'closed_message' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'maintenance_title' => ['nullable', 'string', 'max:150'],
            'maintenance_end_time' => ['nullable', 'string', 'max:50'],
            'maintenance_whitelist_ips' => ['nullable', 'string', 'max:1000'],
            'maintenance_secret' => ['nullable', 'string', 'max:100'],

            // Rekening Pembayaran Manual
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'payment_instructions' => ['nullable', 'string', 'max:1000'],

            // SEO & Analitik
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['free_shipping_from'] = $validated['free_shipping_from'] ?? 0;
        $validated['bulk_minimum'] = $validated['bulk_minimum'] ?? 0;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;
        $validated['store_status'] = $validated['store_status'] ?? 'open';

        Setting::firstOrFail()->update($validated);
        StoreSettingsCache::forget();

        return back()->with('success', 'Pengaturan sistem toko berhasil disimpan');
    }

    public function clearAllCache(): RedirectResponse
    {
        Cache::flush();
        StoreSettingsCache::forget();
        SitemapController::flush();

        return back()->with('success', 'Seluruh cache payload dan data aplikasi berhasil dibersihkan.');
    }

    public function rewarmCache(): RedirectResponse
    {
        Artisan::call('app:cache-warm');

        return back()->with('success', 'Semua cache payload toko (homepage, koleksi, sitemap, pengaturan) berhasil di-rebuild.');
    }

    public function clearViews(): RedirectResponse
    {
        Artisan::call('view:clear');

        return back()->with('success', 'Kompilasi template Blade berhasil di-flush.');
    }

    public function reloadOpcache(): RedirectResponse
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $lsphpRestart = '/home/izim8295/.lsphp_restart.txt';
        if (file_exists('/home/izim8295')) {
            @touch($lsphpRestart);
        }

        return back()->with('success', 'Sinyal reload OPcache / LiteSpeed PHP berhasil dikirim.');
    }

    /**
     * Memperbaiki dan menautkan symlink storage public secara aman di shared hosting tanpa exec().
     */
    public function fixStorageLink(): RedirectResponse
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        if (! file_exists($target)) {
            @mkdir($target, 0755, true);
        }

        if (file_exists($link)) {
            if (is_link($link)) {
                @unlink($link);
            } else {
                return back()->with('error', 'Path public/storage ada tetapi bukan symlink. Harap periksa via file manager hosting.');
            }
        }

        $created = @symlink($target, $link);

        if (! $created && ! file_exists($link)) {
            // Coba via Artisan fallback
            try {
                Artisan::call('storage:link');
                $created = file_exists($link);
            } catch (\Throwable $e) {
                // Ignore blocked function
            }
        }

        if (file_exists($link)) {
            return back()->with('success', 'Symlink storage public berhasil diperiksa dan terhubung.');
        }

        return back()->with('error', 'Gagal membuat symlink otomatis. Pastikan fungsi symlink diizinkan pada PHP hosting Anda.');
    }

    /**
     * Mengambil entri log terkini dari laravel.log dengan parsing level.
     */
    public function getLogs(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');
        if (! file_exists($logPath)) {
            return response()->json([
                'logs' => [],
                'file_size' => '0 B',
                'total' => 0,
            ]);
        }

        $linesToRead = (int) $request->input('lines', 150);
        $levelFilter = strtolower($request->input('level', 'all'));

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $recentLines = array_slice($lines, -$linesToRead);

        $parsedLogs = [];
        $currentLog = null;

        foreach ($recentLines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ([a-zA-Z0-9_-]+)\.([A-Z]+): (.*)$/', $line, $matches)) {
                if ($currentLog) {
                    $parsedLogs[] = $currentLog;
                }

                $level = strtolower($matches[3]);
                $currentLog = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => $level,
                    'message' => $matches[4],
                    'details' => '',
                ];
            } else {
                if ($currentLog) {
                    $currentLog['details'] .= (empty($currentLog['details']) ? '' : "\n").$line;
                }
            }
        }

        if ($currentLog) {
            $parsedLogs[] = $currentLog;
        }

        if ($levelFilter !== 'all') {
            $parsedLogs = array_filter($parsedLogs, fn ($item) => $item['level'] === $levelFilter);
        }

        $parsedLogs = array_reverse(array_values($parsedLogs));

        return response()->json([
            'logs' => $parsedLogs,
            'total' => count($parsedLogs),
            'file_size' => self::formatBytes(filesize($logPath)),
        ]);
    }

    /**
     * Mengosongkan / membersihkan isi laravel.log.
     */
    public function clearLogs(): RedirectResponse
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            File::put($logPath, '');
        }

        return back()->with('success', 'File log laravel.log berhasil dikosongkan.');
    }

    /**
     * Download backup database SQL dump stream.
     */
    public function backupDatabase(): StreamedResponse
    {
        $dbConnection = config('database.default');
        $dbName = config('database.connections.'.$dbConnection.'.database');
        $fileName = 'arafagift-backup-'.date('Y-m-d_H-i-s').'.sql';

        return response()->streamDownload(function () use ($dbName, $dbConnection) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- Arafagift Platform - Database SQL Backup\n");
            fwrite($handle, "-- Database: {$dbName} ({$dbConnection})\n");
            fwrite($handle, '-- Generated: '.date('Y-m-d H:i:s')."\n");
            fwrite($handle, '-- PHP Version: '.PHP_VERSION."\n");
            fwrite($handle, '-- Laravel Version: '.app()->version()."\n");
            fwrite($handle, "-- ========================================================\n\n");

            if ($dbConnection === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
                fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
                fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

                $tables = DB::select('SHOW TABLES');
                $tableKey = 'Tables_in_'.$dbName;

                foreach ($tables as $t) {
                    $tableName = $t->$tableKey ?? current((array) $t);

                    fwrite($handle, "\n-- --------------------------------------------------------\n");
                    fwrite($handle, "-- Struktur tabel `{$tableName}`\n");
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                    $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    if (! empty($createTable)) {
                        $createKey = 'Create Table';
                        $ddl = $createTable[0]->$createKey ?? $createTable[0]->{'Create View'} ?? '';
                        fwrite($handle, $ddl.";\n\n");
                    }

                    $rowsCount = DB::table($tableName)->count();
                    if ($rowsCount > 0) {
                        fwrite($handle, "-- Data tabel `{$tableName}` ({$rowsCount} baris)\n");
                        DB::table($tableName)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $tableName) {
                            foreach ($rows as $row) {
                                $rowArray = (array) $row;
                                $columns = array_map(fn ($col) => "`{$col}`", array_keys($rowArray));
                                $values = array_map(function ($val) {
                                    if (is_null($val)) {
                                        return 'NULL';
                                    }

                                    return "'".addslashes((string) $val)."'";
                                }, array_values($rowArray));

                                $insertSql = "INSERT INTO `{$tableName}` (".implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n";
                                fwrite($handle, $insertSql);
                            }
                        });
                    }
                }

                fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
            }

            fwrite($handle, '-- Backup selesai pada '.date('Y-m-d H:i:s')."\n");
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Jalankan optimasi tabel database MySQL.
     */
    public function optimizeDatabase(): RedirectResponse
    {
        try {
            if (config('database.default') === 'mysql') {
                $dbName = config('database.connections.mysql.database');
                $tables = DB::select('SHOW TABLES');
                $tableKey = 'Tables_in_'.$dbName;
                $optimized = 0;

                foreach ($tables as $t) {
                    $tableName = $t->$tableKey ?? current((array) $t);
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                    $optimized++;
                }

                return back()->with('success', "Berhasil mengoptimalkan {$optimized} tabel database toko.");
            }

            return back()->with('info', 'Optimasi tabel hanya berlaku untuk database MySQL.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengoptimalkan database: '.$e->getMessage());
        }
    }

    /**
     * Kirim email uji coba koneksi SMTP.
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $recipient = $validated['test_email'];
        $setting = Setting::first();
        $storeName = $setting?->store_name ?: 'Arafagift';
        $fromEmail = config('mail.from.address') ?: 'halo@arafagift.id';

        try {
            Mail::html('
                <div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
                    <h2 style="color: #133E2B; margin-top: 0;">Pengujian Koneksi Email Berhasil! 🎉</h2>
                    <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                        Email ini dikirim dari <strong>Panel Administrator '.$storeName.'</strong> untuk memverifikasi bahwa konfigurasi email server dan SMTP berjalan normal.
                    </p>
                    <div style="background: #F3EFE3; border-radius: 8px; padding: 12px 16px; margin: 16px 0; font-size: 13px; color: #1F2D24;">
                        <p style="margin: 4px 0;"><strong>Toko:</strong> '.$storeName.'</p>
                        <p style="margin: 4px 0;"><strong>Driver Mail:</strong> '.config('mail.default').'</p>
                        <p style="margin: 4px 0;"><strong>Pengirim:</strong> '.$fromEmail.'</p>
                        <p style="margin: 4px 0;"><strong>Waktu Uji:</strong> '.now()->timezone('Asia/Makassar')->format('d M Y - H:i:s').' WITA</p>
                    </div>
                    <p style="color: #8A9A90; font-size: 12px; margin-bottom: 0;">'.$storeName.' System Engine • Notifikasi Otomatis</p>
                </div>
            ', function ($message) use ($recipient, $storeName, $fromEmail) {
                $message->to($recipient)
                    ->from($fromEmail, $storeName)
                    ->subject('[Uji Sistem] Verifikasi Pengiriman Email — '.$storeName);
            });

            return back()->with('success', "Email uji coba berhasil dikirim ke {$recipient}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim email uji coba: '.$e->getMessage());
        }
    }

    /**
     * Pratinjau halaman pemeliharaan (503) langsung di tab browser admin.
     */
    public function previewMaintenance()
    {
        $settings = Setting::firstOrFail();

        return response()->view('errors.503', [
            'settings' => $settings,
        ]);
    }

    /**
     * Dapatkan daftar tabel database beserta estimasi baris dan ukuran.
     *
     * @return list<array<string, mixed>>
     */
    private function getDatabaseTables(): array
    {
        $tables = [];

        try {
            if (config('database.default') === 'mysql') {
                $rows = DB::select('SHOW TABLE STATUS');
                foreach ($rows as $r) {
                    $sizeBytes = (int) (($r->Data_length ?? 0) + ($r->Index_length ?? 0));
                    $tables[] = [
                        'name' => $r->Name,
                        'rows' => (int) ($r->Rows ?? 0),
                        'size_bytes' => $sizeBytes,
                        'formatted_size' => self::formatBytes($sizeBytes),
                    ];
                }
            } else {
                $tableNames = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($tableNames as $t) {
                    $name = $t->name;
                    $count = DB::table($name)->count();
                    $tables[] = [
                        'name' => $name,
                        'rows' => $count,
                        'size_bytes' => 0,
                        'formatted_size' => '—',
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore if DB inspection fails
        }

        return $tables;
    }

    private static function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
