<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            100: '#fefce8',
                            200: '#fef9c3',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309'
                        },
                        gray: {
                            50: '#f9fafb',
                            200: '#e5e7eb',
                            800: '#1f2937'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .transition-height {
            transition: max-height 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">
    <div class="p-8 my-8 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-gray-800 text-white shadow-md">
            <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-university text-2xl text-gold-500"></i>
                    <h1 class="text-2xl font-bold">Faculty Management</h1>
                </div>
                <div class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg shadow-sm flex items-center">
                    <i class="far fa-calendar-alt mr-2 text-gold-500"></i>
                    <span>
                        <?php echo $currentSemester ? htmlspecialchars($currentSemester['semester_name'] . ' ' . $currentSemester['academic_year']) : 'Semester Not Set'; ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto px-4 py-6">
            <!-- Alerts -->
            <?php if (isset($_SESSION['success'])): ?>
                <div id="successAlert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md mb-6 flex justify-between items-center">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <p><?php echo htmlspecialchars($_SESSION['success']);
                            unset($_SESSION['success']); ?></p>
                    </div>
                    <button onclick="document.getElementById('successAlert').style.display='none'" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div id="errorAlert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-md mb-6 flex justify-between items-center">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p><?php echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']); ?></p>
                    </div>
                    <button onclick="document.getElementById('errorAlert').style.display='none'" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Controls and Filters -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="mb-4 sm:mb-0">
                        <h2 class="text-lg font-semibold text-gold-600 mb-2">Filter Faculty</h2>
                        <div class="flex flex-wrap gap-3">
                            <div class="w-full sm:w-auto">
                                <label for="departmentFilter" class="block text-sm font-medium text-gray-600 mb-1">Department</label>
                                <select id="departmentFilter" class="w-full sm:w-64 px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                                    <option value="all">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>">
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="w-full sm:w-auto">
                                <label for="statusFilter" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                                <select id="statusFilter" class="w-full sm:w-48 px-3 py-2 border border-gray-200 rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                                    <option value="all">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="searchInput" class="block text-sm font-medium text-gray-600 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search by name..." class="w-full sm:w-64 px-3 py-2 pl-10 border border-gray-200 rounded-md bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6">
                        <button id="tab-chairs" class="tab-button border-gold-500 text-gold-600 py-3 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-user-tie mr-2"></i>Program Chairs
                        </button>
                        <button id="tab-faculty" class="tab-button text-gray-500 hover:text-gray-700 py-3 px-1 border-b-2 border-transparent font-medium text-sm">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>Faculty Members
                        </button>
                        <button id="tab-requests" class="tab-button text-gray-500 hover:text-gray-700 py-3 px-1 border-b-2 border-transparent font-medium text-sm">
                            <i class="fas fa-user-plus mr-2"></i>Pending Requests
                            <?php if (!empty($requests)): ?>
                                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo count($requests); ?></span>
                            <?php endif; ?>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab Content -->
            <div id="tab-content">
                <!-- Program Chairs Section -->
                <div id="chairs-content" class="tab-content">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Program Chairs</h2>
                            <div class="text-sm text-gray-500">
                                <span id="chairs-count"><?php echo count($programChairs); ?></span> total
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full" id="programChairsTable">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($programChairs)): ?>
                                        <tr class="no-results">
                                            <td colspan="5" class="py-6 px-6 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                                                    <p>No program chairs found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($programChairs as $chair): ?>
                                            <tr class="hover:bg-gray-50 table-row"
                                                data-department="<?php echo $chair['department_id']; ?>"
                                                data-status="<?php echo $chair['is_active'] ? 'active' : 'inactive'; ?>"
                                                data-name="<?php echo htmlspecialchars(strtolower($chair['last_name'] . ' ' . $chair['first_name'])); ?>">
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                            <span class="font-medium">
                                                                <?php echo strtoupper(substr($chair['first_name'], 0, 1) . substr($chair['last_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($chair['last_name'] . ', ' . $chair['first_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($chair['program_name']); ?></td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($chair['department_name']); ?></td>
                                                <td class="py-4 px-6">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $chair['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <span class="h-2 w-2 rounded-full <?php echo $chair['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?> mr-1"></span>
                                                        <?php echo $chair['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6 text-right">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $chair['user_id']; ?>">
                                                        <input type="hidden" name="action" value="<?php echo $chair['is_active'] ? 'deactivate' : 'activate'; ?>">
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white <?php echo $chair['is_active'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $chair['is_active'] ? 'focus:ring-red-500' : 'focus:ring-green-500'; ?>">
                                                            <?php if ($chair['is_active']): ?>
                                                                <i class="fas fa-user-times mr-1"></i> Deactivate
                                                            <?php else: ?>
                                                                <i class="fas fa-user-check mr-1"></i> Activate
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Faculty Members Section -->
                <div id="faculty-content" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Faculty Members</h2>
                            <div class="text-sm text-gray-500">
                                <span id="faculty-count"><?php echo count($faculty); ?></span> total
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full" id="facultyTable">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Rank</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Type</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($faculty)): ?>
                                        <tr class="no-results">
                                            <td colspan="6" class="py-6 px-6 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                                                    <p>No faculty members found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($faculty as $member): ?>
                                            <tr class="hover:bg-gray-50 table-row"
                                                data-department="<?php echo $member['department_id']; ?>"
                                                data-status="<?php echo $member['is_active'] ? 'active' : 'inactive'; ?>"
                                                data-name="<?php echo htmlspecialchars(strtolower($member['last_name'] . ' ' . $member['first_name'])); ?>">
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                            <span class="font-medium">
                                                                <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($member['last_name'] . ', ' . $member['first_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($member['academic_rank']); ?></td>
                                                <td class="py-4 px-6">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $member['employment_type'] == 'Full-time' ? 'bg-gold-100 text-gold-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                        <?php echo htmlspecialchars($member['employment_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($member['department_name']); ?></td>
                                                <td class="py-4 px-6">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $member['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                        <span class="h-2 w-2 rounded-full <?php echo $member['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?> mr-1"></span>
                                                        <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6 text-right">
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                        <input type="hidden" name="action" value="<?php echo $member['is_active'] ? 'deactivate' : 'activate'; ?>">
                                                        <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white <?php echo $member['is_active'] ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $member['is_active'] ? 'focus:ring-red-500' : 'focus:ring-green-500'; ?>">
                                                            <?php if ($member['is_active']): ?>
                                                                <i class="fas fa-user-times mr-1"></i> Deactivate
                                                            <?php else: ?>
                                                                <i class="fas fa-user-check mr-1"></i> Activate
                                                            <?php endif; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests Section -->
                <div id="requests-content" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Pending Faculty Requests</h2>
                            <div class="text-sm text-gray-500">
                                <span id="requests-count"><?php echo count($requests); ?></span> requests
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Rank</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employment Type</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($requests)): ?>
                                        <tr>
                                            <td colspan="5" class="py-6 px-6 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-clipboard-check text-gray-300 text-3xl mb-2"></i>
                                                    <p>No pending requests at this time.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($requests as $request): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                            <span class="font-medium">
                                                                <?php echo strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($request['last_name'] . ', ' . $request['first_name']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($request['academic_rank']); ?></td>
                                                <td class="py-4 px-6">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $request['employment_type'] == 'Full-time' ? 'bg-gold-100 text-gold-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                        <?php echo htmlspecialchars($request['employment_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($request['department_name']); ?></td>
                                                <td class="py-4 px-6 text-right">
                                                    <div class="flex justify-end space-x-2">
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                            <input type="hidden" name="action" value="accept">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                                <i class="fas fa-check mr-1"></i> Accept
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                                <i class="fas fa-times mr-1"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Reset all tabs
                    tabs.forEach(t => {
                        t.classList.remove('border-gold-500', 'text-gold-600');
                        t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                    });

                    // Reset all content
                    contents.forEach(c => c.classList.add('hidden'));

                    // Activate clicked tab
                    tab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                    tab.classList.add('border-gold-500', 'text-gold-600');

                    // Show content
                    const contentId = tab.id.replace('tab-', '') + '-content';
                    document.getElementById(contentId).classList.remove('hidden');
                });
            });

            // Department filter functionality
            const departmentFilter = document.getElementById('departmentFilter');
            departmentFilter.addEventListener('change', filterTable);

            // Status filter functionality
            const statusFilter = document.getElementById('statusFilter');
            statusFilter.addEventListener('change', filterTable);

            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', filterTable);

            function filterTable() {
                const selectedDept = departmentFilter.value;
                const selectedStatus = statusFilter.value;
                const searchTerm = searchInput.value.toLowerCase();

                // Filter program chairs
                filterTableRows('programChairsTable', selectedDept, selectedStatus, searchTerm);

                // Filter faculty
                filterTableRows('facultyTable', selectedDept, selectedStatus, searchTerm);

                // Update counts
                updateCounts();
            }

            function filterTableRows(tableId, departmentId, status, searchTerm) {
                const table = document.getElementById(tableId);
                if (!table) return; // Table might not exist

                const rows = table.querySelectorAll('tbody tr.table-row');
                let visibleRows = 0;

                rows.forEach(row => {
                    const rowDept = row.getAttribute('data-department');
                    const rowStatus = row.getAttribute('data-status');
                    const rowName = row.getAttribute('data-name');

                    const deptMatch = departmentId === 'all' || rowDept === departmentId;
                    const statusMatch = status === 'all' || rowStatus === status;
                    const nameMatch = searchTerm === '' || rowName.includes(searchTerm);

                    if (deptMatch && statusMatch && nameMatch) {
                        row.classList.remove('hidden');
                        visibleRows++;
                    } else {
                        row.classList.add('hidden');
                    }
                });

                // Show or hide no results message
                const noResultsRow = table.querySelector('tbody tr.no-results');
                if (noResultsRow) {
                    noResultsRow.style.display = visibleRows === 0 ? '' : 'none';
                }
            }

            function updateCounts() {
                // Update counts for each section
                const chairsCount = document.getElementById('chairs-count');
                const facultyCount = document.getElementById('faculty-count');
                const requestsCount = document.getElementById('requests-count');

                if (chairsCount) {
                    const visibleChairs = document.querySelectorAll('#programChairsTable tbody tr.table-row:not(.hidden)').length;
                    chairsCount.textContent = visibleChairs;
                }

                if (facultyCount) {
                    const visibleFaculty = document.querySelectorAll('#facultyTable tbody tr.table-row:not(.hidden)').length;
                    facultyCount.textContent = visibleFaculty;
                }

                if (requestsCount) {
                    // Requests count doesn't change with filters
                    requestsCount.textContent = document.querySelectorAll('#requests-content table tbody tr:not(.no-results)').length;
                }
            }

            // Initialize with default tab visible
            document.getElementById('tab-chairs').click();

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('#successAlert, #errorAlert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert) {
                        alert.style.opacity = '0';
                        alert.style.transition = 'opacity 1s';
                        setTimeout(() => alert.style.display = 'none', 1000);
                    }
                }, 5000);
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>