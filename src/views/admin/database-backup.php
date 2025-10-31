<?php
ob_start();
?>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-in-left {
        animation: slideInLeft 0.5s ease-in-out;
    }

    @keyframes slideInLeft {
        from {
            transform: translateX(-20px);
            opacity: 0;
        }
    }

    .backup-card {
        transition: all 0.3s ease;
    }

    .backup-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .file-size {
        font-size: 0.875rem;
        color: #6b7280;
    }

    .backup-actions {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .backup-file:hover .backup-actions {
        opacity: 1;
    }
</style>

<div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-yellow-400">
                        Database Backup
                    </h1>
                    <p class="mt-2 text-gray-600">Manage and create database backups</p>
                </div>
                <form method="POST" action="/admin/database-backup?action=create_backup">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Create New Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Automated Backup Scheduler Status -->
        <?php if (isset($scheduler_status)): ?>
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl shadow-md border border-purple-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Automated Backup Scheduler
                        <?php if ($scheduler_status['is_running']): ?>
                            <span class="ml-3 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full animate-pulse">Running</span>
                        <?php else: ?>
                            <span class="ml-3 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">Active</span>
                        <?php endif; ?>
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Last Backup</p>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($scheduler_status['last_backup']); ?></p>
                        <?php if ($scheduler_status['hours_since_last_backup']): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo round($scheduler_status['hours_since_last_backup'], 1); ?> hours ago</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Next Backup</p>
                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($scheduler_status['next_backup']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">Scheduled daily</p>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Total Automated</p>
                        <p class="font-semibold text-gray-900"><?php echo $scheduler_status['total_backups']; ?> backups</p>
                        <?php if (isset($scheduler_stats)): ?>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $scheduler_stats['total_size']; ?> total</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Schedule Time</p>
                        <p class="font-semibold text-gray-900"><?php echo str_pad($scheduler_status['scheduled_hour'], 2, '0', STR_PAD_LEFT); ?>:00</p>
                        <button onclick="document.getElementById('scheduleModal').classList.remove('hidden')" class="text-xs text-blue-600 hover:text-blue-800 mt-1">Change</button>
                    </div>
                </div>

                <div class="flex gap-3">
                    <form method="POST" action="/admin/database-backup?action=trigger_manual_backup" class="inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm flex items-center" <?php echo $scheduler_status['is_running'] ? 'disabled' : ''; ?>>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Trigger Backup Now
                        </button>
                    </form>

                    <button onclick="location.reload()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                        Refresh Status
                    </button>
                </div>
            </div>

            <!-- Schedule Time Modal -->
            <div id="scheduleModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold mb-4">Change Backup Schedule</h3>
                    <form method="POST" action="/admin/database-backup?action=update_schedule">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Backup Time (Hour 0-23)</label>
                            <input type="number" name="scheduled_hour" min="0" max="23" value="<?php echo $scheduler_status['scheduled_hour']; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Enter hour in 24-hour format (e.g., 2 for 2:00 AM, 14 for 2:00 PM)</p>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">Update Schedule</button>
                            <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8'); ?>
                <?php unset($_SESSION['flash']); ?>
            </div>
        <?php endif; ?>

        <!-- Database Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Database Name</p>
                        <p class="text-lg font-bold text-gray-900">
                            <?php echo isset($database_info['name']) ? htmlspecialchars($database_info['name'], ENT_QUOTES, 'UTF-8') : 'Unknown'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Database Size</p>
                        <p class="text-lg font-bold text-gray-900">
                            <?php echo isset($database_info['size_mb']) ? $database_info['size_mb'] : '0'; ?> MB
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Tables</p>
                        <p class="text-lg font-bold text-gray-900">
                            <?php echo isset($database_info['table_count']) ? $database_info['table_count'] : '0'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Last Backup</p>
                        <p class="text-lg font-bold text-gray-900">
                            <?php
                            if (isset($database_info['last_backup']) && $database_info['last_backup']) {
                                echo date('M j, Y', strtotime($database_info['last_backup']['date']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Files Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Backup Files</h2>
                <p class="text-sm text-gray-600 mt-1">Recent database backups (last 30 days are kept)</p>
            </div>

            <div class="p-6">
                <?php if (empty($backup_files)): ?>
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="text-gray-500">No backup files found</p>
                        <p class="text-sm text-gray-400 mt-1">Create your first backup to get started</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($backup_files as $backup): ?>
                            <div class="backup-file flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <!-- Replace this line around line 165 -->
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('M j, Y g:i A', $backup['modified']); ?>
                                            • <?php echo $controller->formatBytes($backup['size']); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="backup-actions flex items-center space-x-2">
                                    <form method="POST" action="/admin/database-backup?action=download_backup" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="text-blue-600 hover:text-blue-900 p-2 rounded transition-colors" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/database-backup?action=delete_backup" class="inline" onsubmit="return confirm('Are you sure you want to delete this backup?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 p-2 rounded transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Information Section -->
        <div class="mt-6 bg-blue-50 rounded-xl border border-blue-200 p-6">
            <h4 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Backup Information
            </h4>
            <ul class="text-xs text-blue-800 space-y-2">
                <li class="flex items-start">
                    <svg class="w-3 h-3 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Backups are automatically compressed and stored in the backups directory
                </li>
                <li class="flex items-start">
                    <svg class="w-3 h-3 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Old backups (older than 30 days) are automatically deleted
                </li>
                <li class="flex items-start">
                    <svg class="w-3 h-3 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Backups include complete database structure and data
                </li>
                <li class="flex items-start">
                    <svg class="w-3 h-3 text-blue-600 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Download backups regularly and store them in a secure location
                </li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>