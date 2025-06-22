<?php
ob_start();
?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-400 p-4 mb-8 rounded-r-lg shadow-sm" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-green-800 font-medium"><?php echo htmlspecialchars($_SESSION['success']);
                                                                unset($_SESSION['success']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-400 p-4 mb-8 rounded-r-lg shadow-sm" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-red-800 font-medium"><?php echo htmlspecialchars($_SESSION['error']);
                                                            unset($_SESSION['error']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Users Card -->
            <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Users</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($userCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/users" class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                            View Users
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-yellow-400 to-yellow-600"></div>
            </div>

            <!-- Total Colleges Card -->
            <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Colleges</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($collegeCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/colleges" class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                            Manage Colleges
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-yellow-400 to-yellow-600"></div>
            </div>

            <!-- Total Departments Card -->
            <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Departments</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($departmentCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/colleges" class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                            Manage Departments
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-yellow-400 to-yellow-600"></div>
            </div>

            <!-- Total Faculty Card -->
            <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Faculty</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($facultyCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/faculty" class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                            View Faculty
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-yellow-400 to-yellow-600"></div>
            </div>

            <!-- Total Schedules Card -->
            <div class="group bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total Schedules</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($scheduleCount, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/schedules" class="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-800 transition-colors duration-200">
                            View Schedules
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="h-1 bg-gradient-to-r from-yellow-400 to-yellow-600"></div>
            </div>
        </div>

        <!-- Recent Activity Logs -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Recent Activity Logs</h2>
                    </div>
                    <a href="/admin/act_logs" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-sm font-medium rounded-lg hover:from-yellow-500 hover:to-yellow-700 transition-all duration-200 shadow-sm hover:shadow-md">
                        View All Logs
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Entity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        try {
                            $stmt = $controller->db->prepare("
                                SELECT al.log_id, al.action_type, al.action_description, al.entity_type, al.entity_id, 
                                       al.created_at, u.first_name, u.last_name
                                FROM activity_logs al
                                JOIN users u ON al.user_id = u.user_id
                                ORDER BY al.created_at DESC
                                LIMIT 5
                            ");
                            $stmt->execute();
                            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($logs)) {
                                echo '<tr><td colspan="5" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            <p class="text-gray-500 font-medium">No recent activity logs found</p>
                                            <p class="text-gray-400 text-sm mt-1">Activity will appear here as users interact with the system</p>
                                        </div>
                                      </td></tr>';
                            } else {
                                foreach ($logs as $index => $log) {
                                    $bgClass = $index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                                    echo '<tr class="' . $bgClass . ' hover:bg-yellow-50 transition-colors duration-200">';
                                    echo '<td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gradient-to-r from-gray-400 to-gray-600 rounded-full flex items-center justify-center text-white text-xs font-medium mr-3">
                                                    ' . strtoupper(substr($log['first_name'], 0, 1)) . strtoupper(substr($log['last_name'], 0, 1)) . '
                                                </div>
                                                <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($log['first_name'] . ' ' . $log['last_name'], ENT_QUOTES, 'UTF-8') . '</div>
                                            </div>
                                          </td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                ' . htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8') . '
                                            </span>
                                          </td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">' . htmlspecialchars($log['entity_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>';
                                    echo '<td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">' . htmlspecialchars($log['action_description'] ?? 'No description', ENT_QUOTES, 'UTF-8') . '</td>';
                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . date('M d, Y', strtotime($log['created_at'])) . '<br><span class="text-xs text-gray-400">' . date('H:i', strtotime($log['created_at'])) . '</span></td>';
                                    echo '</tr>';
                                }
                            }
                        } catch (PDOException $e) {
                            error_log("Activity logs error: " . $e->getMessage());
                            echo '<tr><td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-red-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <p class="text-red-600 font-medium">Error loading activity logs</p>
                                        <p class="text-red-400 text-sm mt-1">Please try refreshing the page or contact support</p>
                                    </div>
                                  </td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="/admin/act_logs" class="text-sm text-yellow-600 hover:text-yellow-700">View All Activity Logs</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>