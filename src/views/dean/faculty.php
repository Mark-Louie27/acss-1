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

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .profile-img {
            object-fit: cover;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #000;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans text-gray-800">
    <div class="p-6 my-8 min-h-screen flex flex-col">
        <!-- Toast Container -->
        <div id="toast-container" class="toast"></div>

        <!-- Modal -->
        <div id="userModal" class="modal z-50 fixed inset-0 flex items-center justify-center">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('userModal').style.display='none'">&times;</span>
                <h2 id="modalTitle" class="text-xl font-bold mb-4">User Information</h2>
                <div id="modalBody" class="space-y-4">
                    <!-- Dynamic content will be populated here -->
                </div>
            </div>
        </div>

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
            <?php if ($error): ?>
                <div id="errorAlert" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-md mb-6 flex justify-between items-center">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <p><?php echo htmlspecialchars($error); ?></p>
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
                        <button id="tab-pending" class="tab-button text-gray-500 hover:text-gray-700 py-3 px-1 border-b-2 border-transparent font-medium text-sm">
                            <i class="fas fa-user-plus mr-2"></i>Pending Users
                            <?php if (!empty($pendingUsers)): ?>
                                <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-1"><?php echo count($pendingUsers); ?></span>
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
                                                data-name="<?php echo htmlspecialchars(strtolower($chair['last_name'] . ' ' . $chair['first_name'])); ?>"
                                                data-user-id="<?php echo $chair['user_id']; ?>"
                                                data-email="<?php echo htmlspecialchars($chair['email']); ?>"
                                                data-program="<?php echo htmlspecialchars($chair['program_name']); ?>"
                                                data-department-name="<?php echo htmlspecialchars($chair['department_name']); ?>">
                                                <td class="py-4 px-6 cursor-pointer" onclick="showUserModal(<?php echo $chair['user_id']; ?>, 'chair')">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10">
                                                            <?php if (!empty($chair['profile_picture']) && file_exists(__DIR__ . '/../../' . $chair['profile_picture'])): ?>
                                                                <img src="/<?php echo htmlspecialchars($chair['profile_picture']); ?>" alt="Profile" class="profile-img">
                                                            <?php else: ?>
                                                                <div class="h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                                    <span class="font-medium">
                                                                        <?php echo strtoupper(substr($chair['first_name'], 0, 1) . substr($chair['last_name'], 0, 1)); ?>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($chair['last_name'] . ', ' . $chair['first_name']); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($chair['email']); ?></div>
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
                                                    <div class="flex justify-end space-x-2">
                                                        <form method="POST" class="inline action-form">
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
                                                        <form method="POST" class="inline action-form">
                                                            <input type="hidden" name="user_id" value="<?php echo $chair['user_id']; ?>">
                                                            <input type="hidden" name="action" value="demote">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                                <i class="fas fa-arrow-down mr-1"></i> Demote
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
                                                data-name="<?php echo htmlspecialchars(strtolower($member['last_name'] . ' ' . $member['first_name'])); ?>"
                                                data-user-id="<?php echo $member['user_id']; ?>"
                                                data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                                data-academic-rank="<?php echo htmlspecialchars($member['academic_rank']); ?>"
                                                data-employment-type="<?php echo htmlspecialchars($member['employment_type']); ?>"
                                                data-department-name="<?php echo htmlspecialchars($member['department_name']); ?>">
                                                <td class="py-4 px-6 cursor-pointer" onclick="showUserModal(<?php echo $member['user_id']; ?>, 'faculty')">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10">
                                                            <?php if (!empty($member['profile_picture']) && file_exists(__DIR__ . '/../../' . $member['profile_picture'])): ?>
                                                                <img src="/<?php echo htmlspecialchars($member['profile_picture']); ?>" alt="Profile" class="profile-img">
                                                            <?php else: ?>
                                                                <div class="h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                                    <span class="font-medium">
                                                                        <?php echo strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)); ?>
                                                                    </span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($member['last_name'] . ', ' . $member['first_name']); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></div>
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
                                                    <div class="flex justify-end space-x-2">
                                                        <form method="POST" class="inline action-form">
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
                                                        <?php if ($member['department_id'] > 0): ?>
                                                            <form method="POST" class="inline action-form">
                                                                <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                                                <input type="hidden" name="action" value="promote">
                                                                <input type="hidden" name="department_id" value="<?php echo $member['department_id']; ?>">
                                                                <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                                    <i class="fas fa-arrow-up mr-1"></i> Promote
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 text-xs italic">No department assigned</span>
                                                        <?php endif; ?>
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

                <!-- Pending Users Section -->
                <div id="pending-content" class="tab-content hidden">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Pending Users</h2>
                            <div class="text-sm text-gray-500">
                                <span id="pending-count"><?php echo count($pendingUsers); ?></span> pending
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (empty($pendingUsers)): ?>
                                        <tr>
                                            <td colspan="4" class="py-6 px-6 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-clipboard-check text-gray-300 text-3xl mb-2"></i>
                                                    <p>No pending users at this time.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pendingUsers as $user): ?>
                                            <tr class="hover:bg-gray-50"
                                                data-user-id="<?php echo $user['user_id']; ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo htmlspecialchars($user['role_name']); ?>"
                                                data-department-name="<?php echo htmlspecialchars($user['department_name']); ?>">
                                                <td class="py-4 px-6 cursor-pointer" onclick="showUserModal(<?php echo $user['user_id']; ?>, 'pending')">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-gold-100 text-gold-700 rounded-full flex items-center justify-center">
                                                            <span class="font-medium">
                                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['last_name'] . ', ' . $user['first_name']); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($user['role_name']); ?></td>
                                                <td class="py-4 px-6"><?php echo htmlspecialchars($user['department_name']); ?></td>
                                                <td class="py-4 px-6 text-right">
                                                    <div class="flex justify-end space-x-2">
                                                        <form method="POST" class="inline action-form">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                                <i class="fas fa-check mr-1"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="inline action-form">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                            <input type="hidden" name="action" value="deactivate">
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
        // Show toast notification
        function showToast(message, bgColor) {
            const toast = document.createElement('div');
            toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
            toast.textContent = message;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 1s';
                setTimeout(() => toast.remove(), 1000);
            }, 5000);
        }

        function showUserModal(userId, type) {
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            let userData = null;

            <?php
            $allUsers = array_merge($programChairs, $faculty, $pendingUsers);
            foreach ($allUsers as $user) {
                $userIdKey = $user['user_id'];
                echo "if ($userIdKey == userId) {";
                echo "userData = " . json_encode([
                    'user_id' => $user['user_id'],
                    'employee_id' => $user['employee_id'] ?? 'N/A',
                    'email' => $user['email'] ?? 'N/A',
                    'title' => $user['title'] ?? '',
                    'first_name' => $user['first_name'] ?? 'Unknown',
                    'middle_name' => $user['middle_name'] ?? '',
                    'last_name' => $user['last_name'] ?? 'Unknown',
                    'suffix' => $user['suffix'] ?? '',
                    'profile_picture' => $user['profile_picture'],
                    'is_active' => $user['is_active'],
                    'program_name' => $user['program_name'] ?? 'N/A',
                    'department_name' => $user['department_name'] ?? 'N/A',
                    'college_name' => $user['college_name'] ?? 'N/A',
                    'academic_rank' => $user['academic_rank'] ?? 'N/A',
                    'employment_type' => $user['employment_type'] ?? 'N/A',
                    'specialization' => $user['specialization'] ?? 'N/A',
                    'expertise_level' => $user['expertise_level'] ?? 'N/A',
                    'role_name' => $user['role_name'] ?? 'N/A'
                ]) . ";";
                echo "}";
            }
            ?>

            if (!userData) {
                modalBody.innerHTML = '<p>User data not found.</p>';
                return;
            }

            // Populate modal
            modalBody.innerHTML = `
                <div class="flex items-start space-x-6 mb-8 pb-6 border-b border-gray-200">
                    <div class="flex-shrink-0">
                        <div class="relative">
                            <img src="${userData.profile_picture ? '/${userData.profile_picture}' : '/default-avatar.png'}" alt="Profile of ${userData.first_name} ${userData.last_name}" class="w-24 h-24 rounded-full object-cover ring-4 ring-gray-50 shadow-md">
                            <div class="absolute -bottom-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-2xl font-semibold text-gray-900 mb-2">${userData.title} ${userData.first_name} ${userData.middle_name} ${userData.last_name} ${userData.suffix}</h4>
                        <div class="flex flex-wrap gap-2 mb-3">
                            ${userData.academic_rank !== 'N/A' ? `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${userData.academic_rank}</span>` : ''}
                            ${userData.employment_type !== 'N/A' ? `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">${userData.employment_type}</span>` : ''}
                        </div>
                        ${userData.specialization !== 'N/A' ? `<p class="text-sm text-gray-600">${userData.specialization} ${userData.expertise_level !== 'N/A' ? '• ' + userData.expertise_level : ''}</p>` : ''}
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-lg p-5">
                        <h5 class="flex items-center text-base font-semibold text-gray-900 mb-4"><i class="fas fa-user text-blue-500 mr-3"></i> Personal Information</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-id-card text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">User ID</span></div>
                                <p class="text-sm font-mono text-gray-900">${userData.user_id}</p>
                            </div>
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-badge text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">Employee ID</span></div>
                                <p class="text-sm font-mono text-gray-900">${userData.employee_id}</p>
                            </div>
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-envelope text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">Email</span></div>
                                <p class="text-sm text-gray-900">${userData.email !== 'N/A' ? `<a href="mailto:${userData.email}" class="text-blue-600 hover:text-blue-800">${userData.email}</a>` : 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-5">
                        <h5 class="flex items-center text-base font-semibold text-gray-900 mb-4"><i class="fas fa-graduation-cap text-green-500 mr-3"></i> Academic Information</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-university text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">College</span></div>
                                <p class="text-sm text-gray-900">${userData.college_name}</p>
                            </div>
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-building text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">Department</span></div>
                                <p class="text-sm text-gray-900">${userData.department_name}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-5">
                        <h5 class="flex items-center text-base font-semibold text-gray-900 mb-4"><i class="fas fa-briefcase text-purple-500 mr-3"></i> Employment Details</h5>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-medal text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">Academic Rank</span></div>
                                <p class="text-sm text-gray-900">${userData.academic_rank !== 'N/A' ? `<span class="inline-flex items-center px-2 py-1 rounded-md bg-blue-100 text-blue-800 text-xs">${userData.academic_rank}</span>` : 'N/A'}</p>
                            </div>
                            <div class="bg-white rounded-md p-4 shadow-sm">
                                <div class="flex items-center mb-2"><i class="fas fa-clock text-gray-400 mr-2"></i><span class="text-xs font-medium text-gray-500 uppercase">Employment Type</span></div>
                                <p class="text-sm text-gray-900">${userData.employment_type !== 'N/A' ? `<span class="inline-flex items-center px-2 py-1 rounded-md bg-green-100 text-green-800 text-xs">${userData.employment_type}</span>` : 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    ${userData.specialization !== 'N/A' ? `
                    <div class="bg-gradient-to-br from-gold-50 to-gray-100 rounded-lg p-5 border border-gold-200">
                        <h5 class="flex items-center text-base font-semibold text-gray-900 mb-4"><i class="fas fa-star text-gold-400 mr-3"></i> Specialization & Expertise</h5>
                        <div class="bg-white/80 rounded-md p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">${userData.specialization}</span>
                                ${userData.expertise_level !== 'N/A' ? `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gold-200 text-gold-800"><i class="fas fa-trophy mr-1"></i> ${userData.expertise_level}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    ` : ''}
                `;
            modal.style.display = 'block';
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => {
                        t.classList.remove('border-gold-500', 'text-gold-600');
                        t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                    });
                    contents.forEach(c => c.classList.add('hidden'));
                    tab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700');
                    tab.classList.add('border-gold-500', 'text-gold-600');
                    const contentId = tab.id.replace('tab-', '') + '-content';
                    document.getElementById(contentId).classList.remove('hidden');
                });
            });

            // Form submission for all actions (including promote - no special handling needed)
            document.querySelectorAll('.action-form').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    try {
                        const response = await fetch('/dean/faculty', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            showToast(data.message || 'Action successful', 'bg-green-500');
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showToast(data.error || 'Action failed', 'bg-red-500');
                        }
                    } catch (error) {
                        showToast('Request failed: ' + error.message, 'bg-red-500');
                    }
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
                if (!table) return;

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

                const noResultsRow = table.querySelector('tbody tr.no-results');
                if (noResultsRow) {
                    noResultsRow.style.display = visibleRows === 0 ? '' : 'none';
                }
            }

            function updateCounts() {
                const chairsCount = document.getElementById('chairs-count');
                const facultyCount = document.getElementById('faculty-count');
                const pendingCount = document.getElementById('pending-count');

                if (chairsCount) {
                    const visibleChairs = document.querySelectorAll('#programChairsTable tbody tr.table-row:not(.hidden)').length;
                    chairsCount.textContent = visibleChairs;
                }

                if (facultyCount) {
                    const visibleFaculty = document.querySelectorAll('#facultyTable tbody tr.table-row:not(.hidden)').length;
                    facultyCount.textContent = visibleFaculty;
                }

                if (pendingCount) {
                    pendingCount.textContent = document.querySelectorAll('#pending-content table tbody tr:not(.no-results)').length;
                }
            }

            // Initialize with default tab visible
            document.getElementById('tab-chairs').click();

            // Auto-hide alerts
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>