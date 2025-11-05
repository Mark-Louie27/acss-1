<?php
ob_start();
?>

<style>
    :root {
        --gold: #D4AF37;
        --white: #FFFFFF;
        --gray-dark: #4B5563;
        --gray-light: #E5E7EB;
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
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

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .modal {
        transition: opacity 0.3s ease;
    }

    .modal.hidden {
        opacity: 0;
        pointer-events: none;
    }

    .modal-content {
        transition: transform 0.3s ease;
    }

    .input-focus {
        transition: all 0.2s ease;
    }

    .input-focus:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
    }

    .btn-gold {
        background-color: var(--gold);
        color: var(--white);
    }

    .btn-gold:hover {
        background-color: #b8972e;
    }

    .loading::after {
        content: '';
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid var(--gold);
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-left: 8px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .tab-active {
        background-color: var(--gold);
        color: var(--white);
    }

    .tab-active:hover {
        background-color: #b8972e;
    }

    .admission-pending {
        background-color: #fef3c7;
        border-left: 4px solid #f59e0b;
    }

    .admission-approved {
        background-color: #d1fae5;
        border-left: 4px solid #10b981;
    }

    .admission-rejected {
        background-color: #fee2e2;
        border-left: 4px solid #ef4444;
    }
</style>

<div class="min-h-screen bg-gray-100 py-6">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 bg-clip-text text-transparent bg-gradient-to-r from-yellow-600 to-yellow-400 slide-in-left">
                        User Management
                    </h1>
                    <p class="mt-2 text-gray-600 slide-in-right">Manage system users, roles, permissions, and admissions</p>
                </div>
                <button onclick="openAddUserModal()" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add User
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="card bg-white rounded-xl shadow-md p-6 border border-gray-200 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($users); ?></p>
                    </div>
                </div>
            </div>
            <div class="card bg-white rounded-xl shadow-md p-6 border border-gray-200 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="card bg-white rounded-xl shadow-md p-6 border border-gray-200 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Roles</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($roles); ?></p>
                    </div>
                </div>
            </div>
            <div class="card bg-white rounded-xl shadow-md p-6 border border-gray-200 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Departments</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($departments); ?></p>
                    </div>
                </div>
            </div>
            <!-- New Admission Stats Card -->
            <div class="card bg-white rounded-xl shadow-md p-6 border border-gray-200 hover:shadow-lg transition-all duration-200">
                <div class="flex items-center">
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Admissions</p>
                        <p class="text-2xl font-bold text-gray-900" id="pending-admissions-count">
                            <?php echo $pendingAdmissionsCount ?? 0; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 mb-6">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" id="searchUsers" placeholder="Search users..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <select id="roleFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="collegeFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-6 border-b border-gray-200">
                <button id="tab-all" class="tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors duration-200 <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'all' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'; ?>" onclick="switchTab('all')">All Users</button>
                <button id="tab-active" class="tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors duration-200 <?php echo isset($_GET['tab']) && $_GET['tab'] === 'active' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'; ?>" onclick="switchTab('active')">Active Users</button>
                <button id="tab-inactive" class="tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors duration-200 <?php echo isset($_GET['tab']) && $_GET['tab'] === 'inactive' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'; ?>" onclick="switchTab('inactive')">Inactive Users</button>
                <button id="tab-admissions" class="tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors duration-200 <?php echo isset($_GET['tab']) && $_GET['tab'] === 'admissions' ? 'tab-active' : 'text-gray-500 hover:text-gray-700'; ?>" onclick="switchTab('admissions')">
                    Pending Admissions
                    <span id="admissions-count" class="ml-1 bg-orange-500 text-white text-xs rounded-full px-2 py-0.5">
                        <?php echo $pendingAdmissionsCount ?? 0; ?>
                    </span>
                </button>
            </nav>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900" id="table-title">Users Directory</h2>
                <div class="text-sm text-gray-500">
                    <span id="visibleCount"><?php echo count($users); ?></span> of <?php echo count($users); ?> users
                </div>
            </div>

            <!-- Horizontal scroll container -->
            <div class="overflow-x-auto">
                <div class="min-w-full inline-block align-middle">
                    <table class="min-w-full divide-y divide-gray-200" id="usersTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[200px]">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[180px]">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[120px]">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[150px]">College</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[150px]">Department</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[100px]">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap min-w-[120px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                            <!-- Regular Users -->
                            <?php foreach ($users as $user): ?>
                                <tr class="user-row <?php echo !$user['is_active'] ? 'pending-user' : ($user['is_active'] ? 'active-user' : 'inactive-user'); ?> hover:bg-gray-50 transition-colors duration-150" data-user-id="<?php echo $user['user_id']; ?>" style="display: <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'all' ? '' : 'none'; ?>">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center min-w-0">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($user['profile_picture'])): ?>
                                                    <img src="<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile picture" class="h-10 w-10 rounded-full object-cover">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-yellow-600">
                                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1">
                                                <div class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($user['title'] . ' ' . $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($user['title'] . ' ' . $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500 truncate">
                                                    @<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="truncate max-w-[160px]" title="<?php echo htmlspecialchars($user['email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($user['email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="truncate max-w-[140px]" title="<?php echo htmlspecialchars($user['college_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($user['college_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="truncate max-w-[140px]" title="<?php echo htmlspecialchars($user['department_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($user['department_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php $isActive = isset($user['is_active']) && $user['is_active']; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <button onclick="viewUser(<?php echo $user['user_id']; ?>)" class="text-blue-600 hover:text-blue-900 p-1 rounded transition-colors" title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            <button onclick="resetPassword(<?php echo $user['user_id']; ?>)" class="text-purple-600 hover:text-purple-900 p-1 rounded transition-colors" title="Reset Password">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                            </button>
                                            <?php if (!$isActive): ?>
                                                <button onclick="approveUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-yellow-600 hover:text-yellow-900 p-1 rounded transition-colors" title="Approve User">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="declineUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-red-600 hover:text-red-900 p-1 rounded transition-colors" title="Decline User">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                            <?php elseif ($isActive): ?>
                                                <button onclick="disableUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-red-600 hover:text-red-900 p-1 rounded transition-colors" title="Disable User">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Pending Admissions -->
                            <?php if (isset($admissions) && is_array($admissions)): ?>
                                <?php foreach ($admissions as $admission): ?>
                                    <tr class="admission-row admission-pending hover:bg-yellow-50 transition-colors duration-150" data-admission-id="<?php echo $admission['admission_id']; ?>" style="display: none;">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center min-w-0">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-orange-600">
                                                            <?php echo strtoupper(substr($admission['first_name'], 0, 1) . substr($admission['last_name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-3 min-w-0 flex-1">
                                                    <div class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['middle_name'] . ' ' . $admission['last_name'] . ' ' . $admission['suffix'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['middle_name'] . ' ' . $admission['last_name'] . ' ' . $admission['suffix'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 truncate">
                                                        @<?php echo htmlspecialchars($admission['username'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </div>
                                                    <div class="text-xs text-orange-600 font-medium">
                                                        Pending Admission
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="truncate max-w-[160px]" title="<?php echo htmlspecialchars($admission['email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($admission['email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                <?php echo htmlspecialchars($admission['role_name'] ?? 'Pending', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="truncate max-w-[140px]" title="<?php echo htmlspecialchars($admission['college_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($admission['college_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="truncate max-w-[140px]" title="<?php echo htmlspecialchars($admission['department_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($admission['department_name'] ?? 'Not assigned', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                Pending Review
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-1">
                                                <button onclick="viewAdmission(<?php echo $admission['admission_id']; ?>)" class="text-blue-600 hover:text-blue-900 p-1 rounded transition-colors" title="View Details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="approveAdmission(<?php echo $admission['admission_id']; ?>, '<?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-green-600 hover:text-green-900 p-1 rounded transition-colors" title="Approve Admission">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="rejectAdmission(<?php echo $admission['admission_id']; ?>, '<?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name'], ENT_QUOTES, 'UTF-8'); ?>')" class="text-red-600 hover:text-red-900 p-1 rounded transition-colors" title="Reject Admission">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center text-sm text-gray-500">
                <span>Scroll horizontally to see more columns →</span>
                <span id="visibleCountBottom"><?php echo count($users); ?> users</span>
            </div>
        </div>
    </div>

    <!-- Decline User Confirmation Modal -->
    <div id="declineUserModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Decline User Account</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to decline <span id="declineUserName" class="font-semibold"></span>? This action will permanently remove the user.
                    </p>
                </div>
                <div class="flex justify-center space-x-3 px-4 py-3">
                    <button onclick="closeDeclineUserModal()" class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDeclineUser()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                        Decline User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Disable User Confirmation Modal -->
    <div id="disableUserModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Disable User Account</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to disable <span id="disableUserName" class="font-semibold"></span>? This action will prevent the user from logging in.
                    </p>
                </div>
                <div class="flex justify-center space-x-3 px-4 py-3">
                    <button onclick="closeDisableUserModal()" class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDisableUser()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                        Disable User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">User Details</h3>
                <button onclick="closeViewUserModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="userDetailsContent" class="space-y-4">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-full max-w-6xl shadow-lg rounded-xl bg-white max-h-[95vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Add New User</h3>
                <button onclick="closeAddUserModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="addUserForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Basic Information Section -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID *</label>
                            <input type="text" name="employee_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                            <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <select name="title" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Title</option>
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" name="middle_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Suffix *</label>
                            <input type="text" name="suffix" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                </div>

                <!-- ==================== PASSWORD GENERATOR (AUTO-SEND EMAIL) ==================== -->
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 p-4 rounded-xl border border-indigo-200 mb-6">
                    <h4 class="text-sm font-semibold text-indigo-900 mb-3 flex items-center">
                        Temporary Password (auto-generated)
                    </h4>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" id="generatedPassword" readonly
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg bg-white font-mono text-sm
                      focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Click Generate to create">
                        <button type="button" onclick="generatePassword()"
                            class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium text-sm transition-colors flex items-center gap-2">
                            Generate
                        </button>
                        <button type="button" onclick="copyPassword()"
                            class="px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium text-sm transition-colors flex items-center gap-2">
                            Copy
                        </button>
                    </div>
                    <p class="text-xs text-indigo-700 mt-2 font-medium">
                        Welcome email with login details will be sent automatically.
                    </p>
                </div>

                <!-- Role and Academic Information Section -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Role & Academic Information</h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                            <select name="role_id" id="roleSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Rank</label>
                            <select name="academic_rank" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Rank</option>
                                <!-- Dynamically populated -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                            <select name="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Type</option>
                                <option value="Regular">Regular</option>
                                <option value="Contractual">Contractual</option>
                                <option value="Part-time">Part-time</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Classification</label>
                            <select name="classification" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Classification</option>
                                <option value="TL">TL</option>
                                <option value="VSL">VSL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Designation</label>
                            <input type="text" name="designation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" placeholder="e.g., Department Head, Coordinator">
                        </div>
                    </div>
                </div>

                <!-- Educational Background Section -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Educational Background</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bachelor's Degree</label>
                            <input type="text" name="bachelor_degree" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Master's Degree</label>
                            <input type="text" name="master_degree" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctorate Degree</label>
                            <input type="text" name="doctorate_degree" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Post-Doctorate Degree</label>
                            <input type="text" name="post_doctorate_degree" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                </div>

                <!-- College and Department Section -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">College & Department</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- College Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">College *</label>
                            <select name="college_id" id="collegeSelect" required onchange="updateDepartments(this.value)"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $college): ?>
                                    <option value="<?php echo $college['college_id']; ?>">
                                        <?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Department Select - Changes based on role -->
                        <div id="departmentSelectContainer">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Department *
                                <span id="multiDeptHint" class="text-xs text-gray-500 hidden">(Can select multiple for Program Chair)</span>
                            </label>

                            <!-- Single Department Select (default) -->
                            <select name="department_id" id="departmentSelect"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Select Department</option>
                            </select>

                            <!-- Multi-Department Select (for Program Chair) - Hidden by default -->
                            <div id="multiDepartmentSelect" class="hidden space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                <p class="text-xs text-gray-500 mb-2">Select one or more departments:</p>
                                <div id="departmentCheckboxes"></div>
                            </div>

                            <!-- Primary Department Select (shown when multiple selected) -->
                            <div id="primaryDepartmentContainer" class="hidden mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Primary Department *</label>
                                <select name="primary_department_id" id="primaryDepartmentSelect"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                    <option value="">Select Primary Department</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">This will be the main department assignment</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeAddUserModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Temporary Password Modal -->
    <div id="tempPasswordModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Temporary Password</h3>
                <p class="text-sm text-gray-600 mb-4">This password will only be shown once. Please copy it now.</p>

                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-1" id="tempUsername"></p>
                    <p class="text-lg font-bold text-red-600" id="tempPassword"></p>
                </div>

                <p class="text-xs text-gray-500 mb-4">User must change this password on first login.</p>

                <button onclick="closeTempPasswordModal()" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors w-full">
                    I've Copied the Password
                </button>
            </div>
        </div>
    </div>

    <!-- Admission Review Modal -->
    <div id="admissionReviewModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-full max-w-4xl shadow-lg rounded-xl bg-white max-h-[95vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Review Admission Request</h3>
                <button onclick="closeAdmissionReviewModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="admissionDetailsContent" class="space-y-6">
                <!-- Populated by JavaScript -->
            </div>
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                <button onclick="closeAdmissionReviewModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button onclick="confirmRejectAdmission()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Reject Admission
                </button>
                <button onclick="confirmApproveAdmission()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    Approve Admission
                </button>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div id="rejectionModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="relative mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Reject Admission</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 mb-4">
                        Please provide a reason for rejecting this admission request:
                    </p>
                    <textarea id="rejectionReason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="Enter rejection reason..."></textarea>
                </div>
                <div class="flex justify-center space-x-3 px-4 py-3">
                    <button onclick="closeRejectionModal()" class="px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-yellow-500 transition-colors">
                        Cancel
                    </button>
                    <button onclick="submitRejection()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                        Submit Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // JavaScript
        let currentUserId = null;
        let currentAdmissionId = null;

        // Dynamic data from PHP
        let dynamicData = {
            titles: <?php echo json_encode($titles ?? []); ?>,
            academicRanks: <?php echo json_encode($academicRanks ?? []); ?>,
            employmentTypes: <?php echo json_encode($employmentTypes ?? []); ?>,
            classifications: <?php echo json_encode($classifications ?? []); ?>,
            departments: <?php echo json_encode($departments ?? []); ?>,
            programs: <?php echo json_encode($programs ?? []); ?>
        };

        console.log('Dynamic data loaded:', dynamicData);

        // Global maps
        window.collegeDepartments = {};
        window.programDepartments = {};

        // Admission data
        const admissionsData = <?php echo json_encode($admissions ?? []); ?>;
        const usersData = <?php echo json_encode($users); ?>;

        // ========================
        // MODAL: Open Add User
        // ========================
        function openAddUserModal() {
            const modal = document.getElementById('addUserModal');
            if (!modal) {
                console.error('Add user modal not found!');
                return;
            }

            // Re-initialize everything when modal opens
            initializeDynamicSelects();
            initializeCollegeDepartments();
            initializeProgramDepartments();

            generatePassword();

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // ========================
        // MODAL: Close Add User
        // ========================
        function closeAddUserModal() {
            const modal = document.getElementById('addUserModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                document.getElementById('addUserForm')?.reset();

                // Reset multi-department UI
                document.getElementById('multiDeptHint').classList.add('hidden');
                document.getElementById('departmentSelect').classList.remove('hidden');
                document.getElementById('multiDepartmentSelect').classList.add('hidden');
                document.getElementById('primaryDepartmentContainer').classList.add('hidden');
            }
        }

        // ========================
        // Password Generation
        // ========================
        function generatePassword() {
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*";
            let pass = "";
            for (let i = 0; i < 14; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('generatedPassword').value = pass;
        }

        function copyPassword() {
            const el = document.getElementById('generatedPassword');
            el.select();
            document.execCommand('copy');
            alert('Password copied to clipboard!');
        }

        // ========================
        // Initialize Dynamic Selects
        // ========================
        function initializeDynamicSelects() {
            // Title
            const titleSelect = document.querySelector('select[name="title"]');
            if (titleSelect && Array.isArray(dynamicData.titles)) {
                titleSelect.innerHTML = '<option value="">Select Title</option>';
                dynamicData.titles.forEach(t => {
                    const opt = new Option(t, t);
                    titleSelect.add(opt);
                });
            }

            // Academic Rank
            const rankSelect = document.querySelector('select[name="academic_rank"]');
            if (rankSelect && Array.isArray(dynamicData.academicRanks)) {
                rankSelect.innerHTML = '<option value="">Select Rank</option>';
                dynamicData.academicRanks.forEach(r => {
                    const opt = new Option(r, r);
                    rankSelect.add(opt);
                });
            }

            // Employment Type
            const empSelect = document.querySelector('select[name="employment_type"]');
            if (empSelect && Array.isArray(dynamicData.employmentTypes)) {
                empSelect.innerHTML = '<option value="">Select Type</option>';
                dynamicData.employmentTypes.forEach(t => {
                    const opt = new Option(t, t);
                    empSelect.add(opt);
                });
            }

            // Classification
            const classSelect = document.querySelector('select[name="classification"]');
            if (classSelect && Array.isArray(dynamicData.classifications)) {
                classSelect.innerHTML = '<option value="">Select Classification</option>';
                dynamicData.classifications.forEach(c => {
                    const opt = new Option(c, c);
                    classSelect.add(opt);
                });
            }
        }

        // ========================
        // College → Department Mapping
        // ========================
        function initializeCollegeDepartments() {
            window.collegeDepartments = {};

            if (!Array.isArray(dynamicData.departments)) {
                console.error('Departments data missing or invalid');
                return;
            }

            dynamicData.departments.forEach(dept => {
                const cid = dept.college_id;
                if (!window.collegeDepartments[cid]) {
                    window.collegeDepartments[cid] = [];
                }
                window.collegeDepartments[cid].push({
                    id: dept.department_id,
                    name: dept.department_name
                });
            });

            console.log('College → Department map built:', window.collegeDepartments);
        }

        // ========================
        // Program → Department Mapping
        // ========================
        function initializeProgramDepartments() {
            window.programDepartments = {};

            if (!Array.isArray(dynamicData.programs)) {
                console.error('Programs data missing or invalid');
                return;
            }

            dynamicData.programs.forEach(p => {
                const did = p.department_id;
                if (!window.programDepartments[did]) {
                    window.programDepartments[did] = [];
                }
                window.programDepartments[did].push({
                    id: p.program_id,
                    name: p.program_name
                });
            });

            console.log('Program → Department map built:', window.programDepartments);
        }

        // ========================
        // Role Change Handler (Single vs Multi Department)
        // ========================
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('roleSelect');
            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    const roleId = parseInt(this.value);
                    const isProgramChair = roleId === 5;

                    // Show/hide multi-department interface
                    const multiHint = document.getElementById('multiDeptHint');
                    const singleSelect = document.getElementById('departmentSelect');
                    const multiSelect = document.getElementById('multiDepartmentSelect');
                    const primaryContainer = document.getElementById('primaryDepartmentContainer');

                    if (multiHint) multiHint.classList.toggle('hidden', !isProgramChair);
                    if (singleSelect) singleSelect.classList.toggle('hidden', isProgramChair);
                    if (multiSelect) multiSelect.classList.toggle('hidden', !isProgramChair);

                    // Reset selections when switching
                    if (isProgramChair) {
                        if (singleSelect) singleSelect.value = '';
                        updateMultiDepartmentCheckboxes();
                    } else {
                        document.querySelectorAll('input[name="department_ids[]"]').forEach(cb => cb.checked = false);
                        if (primaryContainer) primaryContainer.classList.add('hidden');
                    }
                });
            }
        });

        // ========================
        // Update Department Dropdowns
        // ========================
        function updateDepartments(collegeId) {
            const roleId = parseInt(document.getElementById('roleSelect')?.value);
            const isProgramChair = roleId === 5;

            if (isProgramChair) {
                updateMultiDepartmentCheckboxes();
            } else {
                // Single-select logic
                const deptSelect = document.getElementById('departmentSelect');
                if (!deptSelect) return;

                deptSelect.innerHTML = '<option value="">Select Department</option>';

                if (!collegeId) return;

                const depts = window.collegeDepartments[collegeId] || [];
                depts.forEach(d => {
                    const opt = new Option(d.name, d.id);
                    deptSelect.add(opt);
                });

                console.log(`Updated departments for college ${collegeId}:`, depts);
            }

            // Clear program selections when college changes
            clearProgramSelections();
        }

        // ========================
        // Multi-Department Checkboxes (Program Chair)
        // ========================
        function updateMultiDepartmentCheckboxes() {
            const collegeId = document.getElementById('collegeSelect').value;
            const container = document.getElementById('departmentCheckboxes');

            if (!container) return;

            container.innerHTML = '';

            if (!collegeId || !window.collegeDepartments[collegeId]) return;

            const departments = window.collegeDepartments[collegeId];

            departments.forEach(dept => {
                const div = document.createElement('div');
                div.className = 'flex items-center space-x-2 p-2 hover:bg-gray-50 rounded';
                div.innerHTML = `
            <input type="checkbox" 
                   name="department_ids[]" 
                   value="${dept.id}" 
                   id="dept_${dept.id}"
                   onchange="updatePrimaryDepartmentOptions()"
                   class="w-4 h-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
            <label for="dept_${dept.id}" class="text-sm text-gray-700 cursor-pointer flex-1">
                ${dept.name}
            </label>
        `;
                container.appendChild(div);
            });
        }

        // ========================
        // Update Primary Department Options
        // ========================
        function updatePrimaryDepartmentOptions() {
            const checkedBoxes = document.querySelectorAll('input[name="department_ids[]"]:checked');
            const primarySelect = document.getElementById('primaryDepartmentSelect');
            const primaryContainer = document.getElementById('primaryDepartmentContainer');

            if (!primarySelect || !primaryContainer) return;

            primarySelect.innerHTML = '<option value="">Select Primary Department</option>';

            if (checkedBoxes.length > 1) {
                // Show primary selector if multiple departments selected
                primaryContainer.classList.remove('hidden');

                checkedBoxes.forEach(cb => {
                    const deptId = cb.value;
                    const deptName = cb.nextElementSibling.textContent.trim();
                    const option = new Option(deptName, deptId);
                    primarySelect.add(option);
                });
            } else if (checkedBoxes.length === 1) {
                // Auto-set as primary if only one selected
                primaryContainer.classList.add('hidden');
                primarySelect.innerHTML = `<option value="${checkedBoxes[0].value}" selected>${checkedBoxes[0].nextElementSibling.textContent.trim()}</option>`;
            } else {
                primaryContainer.classList.add('hidden');
            }
        }

        // ========================
        // Update Program Dropdowns
        // ========================
        function updatePrograms(departmentId) {
            const selects = [
                document.querySelector('select[name="primary_program_id"]'),
                document.querySelector('select[name="secondary_program_id"]')
            ];

            selects.forEach(select => {
                if (!select) return;
                const current = select.value;
                select.innerHTML = '<option value="">Select Program</option>';

                if (!departmentId) return;

                const progs = window.programDepartments[departmentId] || [];
                progs.forEach(p => {
                    const opt = new Option(p.name, p.id);
                    if (p.id == current) opt.selected = true;
                    select.add(opt);
                });
            });
        }

        function clearProgramSelections() {
            const primaryProg = document.getElementById('primaryProgramSelect');
            const secondaryProg = document.getElementById('secondaryProgramSelect');

            if (primaryProg) primaryProg.innerHTML = '<option value="">Select Primary Program</option>';
            if (secondaryProg) secondaryProg.innerHTML = '<option value="">Select Secondary Program</option>';
        }

        // ========================
        // Form Submission Handler
        // ========================
        // Replace your form submission handler with this:
        document.addEventListener('DOMContentLoaded', function() {
            const addUserForm = document.getElementById('addUserForm');

            if (addUserForm) {
                addUserForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    console.log('Form submitted');

                    const formData = new FormData(this);
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;

                    // Validate password
                    const password = document.getElementById('generatedPassword').value;
                    if (!password || password.length < 6) {
                        showErrorModal('Validation Error', 'Please generate a temporary password (minimum 6 characters).');
                        return;
                    }
                    formData.set('temporary_password', password);

                    // Add action
                    formData.set('action', 'add');

                    // Handle Program Chair multi-department
                    const roleId = parseInt(document.getElementById('roleSelect').value);
                    if (roleId === 5) { // Program Chair
                        const checkedDepts = document.querySelectorAll('input[name="department_ids[]"]:checked');

                        if (checkedDepts.length === 0) {
                            showErrorModal('Validation Error', 'Please select at least one department for Program Chair');
                            return;
                        }

                        // Remove any existing department_ids[] entries
                        formData.delete('department_ids[]');

                        // Add all checked departments
                        checkedDepts.forEach(checkbox => {
                            formData.append('department_ids[]', checkbox.value);
                        });

                        console.log('Selected departments:', Array.from(checkedDepts).map(cb => cb.value));

                        if (checkedDepts.length > 1) {
                            const primaryDept = document.getElementById('primaryDepartmentSelect').value;
                            if (!primaryDept) {
                                showErrorModal('Validation Error', 'Please select a primary department');
                                return;
                            }
                            formData.set('primary_department_id', primaryDept);
                            console.log('Primary department:', primaryDept);
                        } else if (checkedDepts.length === 1) {
                            // Single department - set as both department_id and primary
                            formData.set('department_id', checkedDepts[0].value);
                            formData.set('primary_department_id', checkedDepts[0].value);
                            console.log('Single department:', checkedDepts[0].value);
                        }
                    } else {
                        // For other roles, ensure single department is set
                        const deptId = document.getElementById('departmentSelect').value;
                        if (deptId) {
                            formData.set('department_id', deptId);
                            console.log('Single department for non-chair:', deptId);
                        }
                    }

                    // Log all form data for debugging
                    console.log('FormData contents:');
                    for (let [key, value] of formData.entries()) {
                        console.log(`  ${key}: ${value}`);
                    }

                    // Show loading state
                    submitButton.textContent = 'Adding User...';
                    submitButton.disabled = true;

                    fetch('/admin/users?action=add', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);

                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                // Get the actual response text to see what went wrong
                                return response.text().then(text => {
                                    console.error('Non-JSON response:', text);
                                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);

                            if (data.success) {
                                closeAddUserModal();
                                showTempPassword(data.temporary_password, data.username || formData.get('username'));
                                setTimeout(() => {
                                    location.reload();
                                }, 3000);
                            } else {
                                showErrorModal('Error Adding User', data.error || data.message || 'Unknown error occurred');
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            showErrorModal('Network Error', error.message || 'An error occurred while adding the user. Please try again.');
                        })
                        .finally(() => {
                            submitButton.textContent = originalText;
                            submitButton.disabled = false;
                        });
                });

                console.log('✅ Form submit handler attached');
            } else {
                console.error('❌ Add user form not found');
            }
        });

        // ========================
        // Temporary Password Modal
        // ========================
        function showTempPassword(password, username) {
            document.getElementById('tempUsername').textContent = 'Username: ' + username;
            document.getElementById('tempPassword').textContent = password;
            document.getElementById('tempPasswordModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeTempPasswordModal() {
            document.getElementById('tempPasswordModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // ========================
        // Password Reset Function
        // ========================
        function resetPassword(userId) {
            showConfirmationModal(
                'Reset Password',
                'Are you sure you want to reset this user\'s password? They will receive a new temporary password.',
                () => {
                    const formData = new FormData();
                    formData.append('action', 'reset_password');
                    formData.append('user_id', userId);
                    formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

                    // Show loading
                    const modal = showLoadingModal('Resetting password...');

                    fetch('/admin/users', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            modal.remove();
                            if (data.success) {
                                showTempPassword(data.temporary_password, data.username);
                            } else {
                                showErrorModal('Password Reset Failed', data.error || 'Unknown error occurred');
                            }
                        })
                        .catch(error => {
                            modal.remove();
                            console.error('Password reset error:', error);
                            showErrorModal('Network Error', 'An error occurred while resetting the password. Please try again.');
                        });
                }
            );
        }

        // ========================
        // User Status Functions
        // ========================
        function approveUser(userId, userName) {
            showConfirmationModal(
                'Approve User',
                `Are you sure you want to approve ${userName}?`,
                () => {
                    const formData = new FormData();
                    formData.append('action', 'activate');
                    formData.append('user_id', userId);
                    formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

                    const modal = showLoadingModal('Approving user...');

                    fetch('/admin/users', { // ← No query params in URL
                            method: 'POST',
                            body: formData // ← FormData in body
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            modal.remove();
                            console.log('Response data:', data);

                            if (data.success) {
                                showSuccessModal('Success', data.message || 'User approved successfully');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showErrorModal('Approval Failed', data.error || 'Unknown error occurred');
                            }
                        })
                        .catch(error => {
                            modal.remove();
                            console.error('Activate user error:', error);
                            showErrorModal('Network Error', 'An error occurred while approving the user.');
                        });
                }
            );
        }

        function disableUser(userId, userName) {
            showConfirmationModal(
                'Disable User',
                `Are you sure you want to disable ${userName}?`,
                () => {
                    const formData = new FormData();
                    formData.append('action', 'deactivate');
                    formData.append('user_id', userId);
                    formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

                    const modal = showLoadingModal('Disabling user...');

                    fetch('/admin/users', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            modal.remove();
                            if (data.success) {
                                location.reload();
                            } else {
                                showErrorModal('Disable Failed', data.error || 'Unknown error occurred');
                            }
                        })
                        .catch(error => {
                            modal.remove();
                            console.error('Deactivate user error:', error);
                            showErrorModal('Network Error', 'An error occurred while disabling the user.');
                        });
                }
            );
        }

        // Keep showPassword as alias for resetPassword
        function showPassword(userId) {
            resetPassword(userId);
        }

        // ========================
        // Loading Modal Helper
        // ========================
        function showLoadingModal(message = 'Processing...') {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                    <div class="loading-spinner w-6 h-6 border-2 border-yellow-600 border-t-transparent rounded-full animate-spin"></div>
                    <span class="text-gray-700">${message}</span>
                </div>
            `;
            document.body.appendChild(modal);
            return modal;
        }

        function disableUser(userId, userName) {
            currentUserId = userId;
            document.getElementById('disableUserName').textContent = userName;
            document.getElementById('disableUserModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDisableUserModal() {
            document.getElementById('disableUserModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentUserId = null;
        }

        function confirmDisableUser() {
            if (!currentUserId) return;

            const formData = new FormData();
            formData.append('action', 'deactivate');
            formData.append('user_id', currentUserId);
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

            const modal = showLoadingModal('Disabling user...');

            fetch('/admin/users', { // ← No query params
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    modal.remove();
                    if (data.success) {
                        closeDisableUserModal();
                        showSuccessModal('Success', data.message || 'User disabled successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorModal('Disable Failed', data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    modal.remove();
                    console.error('Disable user error:', error);
                    showErrorModal('Network Error', 'An error occurred while disabling the user.');
                });
        }

        function declineUser(userId, userName) {
            currentUserId = userId;
            document.getElementById('declineUserName').textContent = userName;
            document.getElementById('declineUserModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDeclineUserModal() {
            document.getElementById('declineUserModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentUserId = null;
        }

        function confirmDeclineUser() {
            if (!currentUserId) return;

            const formData = new FormData();
            formData.append('action', 'deactivate');
            formData.append('user_id', currentUserId);
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

            const modal = showLoadingModal('Declining user...');

            fetch('/admin/users', { // ← No query params
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    modal.remove();
                    if (data.success) {
                        closeDeclineUserModal();
                        showSuccessModal('Success', data.message || 'User declined successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showErrorModal('Decline Failed', data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    modal.remove();
                    console.error('Decline user error:', error);
                    showErrorModal('Network Error', 'An error occurred while declining the user.');
                });
        }

        // ========================
        // View User Modal
        // ========================
        function viewUser(id) {
            const user = usersData.find(u => parseInt(u.user_id) === parseInt(id));
            if (!user) return alert('User not found');

            const safe = (v, f = 'N/A') => (v === null || v === undefined || v === '' || (typeof v === 'string' && v.trim() === '')) ? f : v;

            // Full name 
            const fullName = [safe(user.title), safe(user.first_name), safe(user.middle_name), safe(user.last_name), safe(user.suffix)]
                .filter(Boolean).join(' ') || 'No Name';

            const initials = ((user.first_name?.[0] || '') + (user.last_name?.[0] || '')).toUpperCase() || 'UU';

            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                try {
                    return new Date(dateStr).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                } catch {
                    return dateStr;
                }
            };

            let sections = [];

            // Basic Info (Always show these essential fields)
            const basicInfoFields = [{
                    label: 'Employee ID',
                    value: user.employee_id
                },
                {
                    label: 'Username',
                    value: user.username
                },
                {
                    label: 'Email',
                    value: user.email
                },
                {
                    label: 'Phone',
                    value: user.phone
                },
                {
                    label: 'Role',
                    value: user.role_name
                },
            ];

            const basicInfoHTML = basicInfoFields
                .filter(field => field.value != null && field.value !== '')
                .map(field => `<div><label class="font-medium text-gray-700">${field.label}</label><p class="mt-1">${safe(field.value)}</p></div>`)
                .join('');

            if (basicInfoHTML) {
                sections.push(`
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-6 border-b">
                        ${basicInfoHTML}
                        <div>
                            <label class="font-medium text-gray-700">Status</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                `);
            }

            // College & Department (Show if available)
            if (user.college_name || user.department_name) {
                sections.push(`
                    <div class="mb-6 pb-6 border-b">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Assignment</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${user.college_name ? `<div><label class="font-medium text-gray-700">College</label><p class="mt-1">${safe(user.college_name)}</p></div>` : ''}
                            ${user.department_name ? `<div><label class="font-medium text-gray-700">Department</label><p class="mt-1">${safe(user.department_name)}</p></div>` : ''}
                        </div>
                    </div>
                `);
            }

            // Academic Info (Faculty only)
            const academicFields = [{
                    label: 'Academic Rank',
                    value: user.academic_rank
                },
                {
                    label: 'Employment Type',
                    value: user.employment_type
                },
                {
                    label: 'Classification',
                    value: user.classification
                },
                {
                    label: 'Designation',
                    value: user.designation
                }
            ];

            const academicHTML = academicFields
                .filter(field => field.value != null && field.value !== '')
                .map(field => `<div><label class="font-medium text-gray-700">${field.label}</label><p class="mt-1">${safe(field.value)}</p></div>`)
                .join('');

            if (academicHTML) {
                sections.push(`
                    <div class="mb-6 pb-6 border-b">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Academic Information</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">${academicHTML}</div>
                    </div>
                `);
            }

            // Educational Background (Show if any degree is available)
            const eduFields = [{
                    label: 'Bachelor',
                    value: user.bachelor_degree
                },
                {
                    label: 'Master\'s',
                    value: user.master_degree
                },
                {
                    label: 'Doctorate',
                    value: user.doctorate_degree
                },
                {
                    label: 'Post-Doctorate',
                    value: user.post_doctorate_degree
                }
            ];

            const eduHTML = eduFields
                .filter(field => field.value != null && field.value !== '')
                .map(field => `<p><span class="font-medium">${field.label}:</span> ${safe(field.value)}</p>`)
                .join('');

            if (eduHTML) {
                sections.push(`
                    <div class="mb-6 pb-6 border-b">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Educational Background</h5>
                        <div class="space-y-1">${eduHTML}</div>
                    </div>
                `);
            }

            // Programs (Show if either program is available)
            if (user.primary_program_name || user.secondary_program_name) {
                sections.push(`
                    <div class="mb-6 pb-6 border-b">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Programs</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            ${user.primary_program_name ? `<div><label class="font-medium text-gray-700">Primary</label><p class="mt-1">${safe(user.primary_program_name)}</p></div>` : ''}
                            ${user.secondary_program_name ? `<div><label class="font-medium text-gray-700">Secondary</label><p class="mt-1">${safe(user.secondary_program_name)}</p></div>` : ''}
                        </div>
                    </div>
                `);
            }

            // Teaching Load (Show only if at least one field has data)
            const loadFields = [{
                    label: 'Max Hours',
                    value: user.max_hours
                },
                {
                    label: 'Equivalent Teaching Load',
                    value: user.equiv_teaching_load
                },
                {
                    label: 'Lecture Hours',
                    value: user.total_lecture_hours
                },
                {
                    label: 'Lab Hours',
                    value: user.total_laboratory_hours
                },
                {
                    label: 'Lab Hours ×0.75',
                    value: user.total_laboratory_hours_x075
                },
                {
                    label: 'No. of Preparations',
                    value: user.no_of_preparation
                },
                {
                    label: 'Advisory Class',
                    value: user.advisory_class
                },
                {
                    label: 'Actual Teaching Load',
                    value: user.actual_teaching_loads
                },
                {
                    label: 'Total Working Load',
                    value: user.total_working_load
                },
                {
                    label: 'Excess Hours',
                    value: user.excess_hours
                }
            ];

            const loadHTML = loadFields
                .filter(field => field.value != null && field.value !== '')
                .map(field => `<div><label class="font-medium text-gray-700">${field.label}</label><p class="mt-1">${safe(field.value)}</p></div>`)
                .join('');

            if (loadHTML) {
                sections.push(`
                    <div class="mb-6 pb-6 border-b">
                        <h5 class="text-sm font-semibold text-gray-900 mb-3">Teaching Load</h5>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">${loadHTML}</div>
                    </div>
                `);
            }

            // Account Info (Always show these system fields)
            sections.push(`
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="font-medium text-gray-700">Account Created</label><p class="mt-1">${formatDate(user.created_at)}</p></div>
                    <div><label class="font-medium text-gray-700">Last Updated</label><p class="mt-1">${formatDate(user.updated_at)}</p></div>
                </div>
            `);

            // Final Modal Content
            const content = `
                <div class="flex items-center space-x-4 mb-6 pb-4 border-b">
                    ${user.profile_picture ? 
                        `<img src="${safe(user.profile_picture)}" alt="Profile" class="h-16 w-16 rounded-full object-cover border-2 border-yellow-400">` :
                        `<div class="h-16 w-16 rounded-full bg-yellow-100 flex items-center justify-center border-2 border-yellow-400">
                            <span class="text-xl font-bold text-yellow-600">${initials}</span>
                        </div>`
                    }
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">${fullName}</h4>
                        <p class="text-sm text-gray-600">${safe(user.role_name)} • ${safe(user.employee_id)}</p>
                    </div>
                </div>
                ${sections.join('')}
            `;

            document.getElementById('userDetailsContent').innerHTML = content;
            document.getElementById('viewUserModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeViewUserModal() {
            document.getElementById('viewUserModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // ========================
        // Modal Helpers
        // ========================
        function showConfirmationModal(title, message, onConfirm) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-md w-full mx-4">
                    <div class="flex items-center gap-3 p-6 border-b border-gray-200">
                        <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600">${message}</p>
                    </div>
                    <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button class="confirm-btn px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            Confirm
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            modal.querySelector('.confirm-btn').addEventListener('click', function() {
                modal.remove();
                onConfirm();
            });
        }

        function showErrorModal(title, message) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-md w-full mx-4">
                    <div class="flex items-center gap-3 p-6 border-b border-gray-200">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600">${message}</p>
                    </div>
                    <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // ==================== NEW ADMISSION FUNCTIONS ====================

        // Admission Management Functions
        function viewAdmission(admissionId) {
            const admission = admissionsData.find(a => parseInt(a.admission_id) === parseInt(admissionId));
            if (!admission) return alert('Admission not found');

            const safe = (v, f = 'N/A') => (v === null || v === undefined || v === '' || (typeof v === 'string' && v.trim() === '')) ? f : v;

            const fullName = [safe(admission.title), safe(admission.first_name), safe(admission.middle_name), safe(admission.last_name), safe(admission.suffix)]
                .filter(Boolean).join(' ') || 'No Name';

            const content = `
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <span class="text-lg font-bold text-orange-600">
                                ${(admission.first_name?.[0] || '') + (admission.last_name?.[0] || '').toUpperCase() || 'AA'}
                            </span>
                        </div>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">${fullName}</h4>
                            <p class="text-sm text-orange-600 font-medium">Pending Admission Review</p>
                            <p class="text-xs text-gray-500">Submitted: ${new Date(admission.submitted_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">Personal Information</h5>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.employee_id)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.username)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.email)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.phone)}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">Academic Information</h5>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    ${safe(admission.role_name)}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">College</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.college_name)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.department_name)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Rank</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.academic_rank)}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <h5 class="text-sm font-semibold text-gray-900 border-b pb-2">Additional Information</h5>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employment Type</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.employment_type)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Classification</label>
                            <p class="mt-1 text-sm text-gray-900">${safe(admission.classification)}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Submitted Date</label>
                            <p class="mt-1 text-sm text-gray-900">${new Date(admission.submitted_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('admissionDetailsContent').innerHTML = content;
            currentAdmissionId = admissionId;
            document.getElementById('admissionReviewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAdmissionReviewModal() {
            document.getElementById('admissionReviewModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentAdmissionId = null;
        }

        function approveAdmission(admissionId, userName) {
            currentAdmissionId = admissionId;
            showConfirmationModal(
                'Approve Admission',
                `Are you sure you want to approve ${userName}'s admission request? This will create their user account.`,
                confirmApproveAdmission
            );
        }

        function confirmApproveAdmission() {
            if (!currentAdmissionId) return;

            const formData = new FormData();
            formData.append('action', 'approve_admission');
            formData.append('admission_id', currentAdmissionId);
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

            const modal = showLoadingModal('Approving admission...');

            fetch('/admin/users', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    modal.remove();
                    if (data.success) {
                        closeAdmissionReviewModal();
                        showSuccessModal('Admission Approved', data.message || 'Admission approved successfully');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showErrorModal('Approval Failed', data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    modal.remove();
                    console.error('Approve admission error:', error);
                    showErrorModal('Network Error', 'An error occurred while approving the admission.');
                });
        }

        function rejectAdmission(admissionId, userName) {
            currentAdmissionId = admissionId;
            document.getElementById('rejectionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeRejectionModal() {
            document.getElementById('rejectionModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentAdmissionId = null;
        }

        function submitRejection() {
            const reason = document.getElementById('rejectionReason').value.trim();
            if (!reason) {
                alert('Please provide a rejection reason');
                return;
            }

            if (!currentAdmissionId) return;

            const formData = new FormData();
            formData.append('action', 'reject_admission');
            formData.append('admission_id', currentAdmissionId);
            formData.append('rejection_reason', reason);
            formData.append('csrf_token', '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>');

            const modal = showLoadingModal('Rejecting admission...');

            fetch('/admin/users', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    modal.remove();
                    if (data.success) {
                        closeRejectionModal();
                        showSuccessModal('Admission Rejected', data.message || 'Admission rejected successfully');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showErrorModal('Rejection Failed', data.error || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    modal.remove();
                    console.error('Reject admission error:', error);
                    showErrorModal('Network Error', 'An error occurred while rejecting the admission.');
                });
        }

        // Success Modal Helper
        function showSuccessModal(title, message) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg max-w-md w-full mx-4">
                    <div class="flex items-center gap-3 p-6 border-b border-gray-200">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600">${message}</p>
                    </div>
                    <div class="flex justify-end gap-3 p-6 border-t border-gray-200">
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            OK
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // ========================
        // Tab Switching
        // ========================
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
            document.getElementById(`tab-${tab}`).classList.add('tab-active');

            let title = `${tab.charAt(0).toUpperCase() + tab.slice(1)} Users`;
            if (tab === 'admissions') title = 'Pending Admissions';
            document.getElementById('table-title').textContent = title;

            // Show/hide rows based on tab
            document.querySelectorAll('.user-row').forEach(row => {
                row.style.display = 'none';
                if (tab === 'all') row.style.display = '';
                else if (tab === 'active' && row.classList.contains('active-user')) row.style.display = '';
                else if (tab === 'inactive' && row.classList.contains('inactive-user')) row.style.display = '';
            });

            // Show/hide admission rows
            document.querySelectorAll('.admission-row').forEach(row => {
                row.style.display = tab === 'admissions' ? '' : 'none';
            });

            // Update visible count
            let visibleCount = 0;
            if (tab === 'admissions') {
                visibleCount = document.querySelectorAll('.admission-row').length;
            } else {
                visibleCount = document.querySelectorAll('.user-row[style=""]').length;
            }

            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('visibleCountBottom').textContent = `${visibleCount} ${tab === 'admissions' ? 'admissions' : 'users'}`;

            window.history.pushState({}, '', `?tab=${tab}`);
            if (tab !== 'admissions') filterTable();
        }

        // ========================
        // Table Filtering
        // ========================
        function filterTable() {
            const search = document.getElementById('searchUsers').value.toLowerCase();
            const role = document.getElementById('roleFilter').value.toLowerCase();
            const college = document.getElementById('collegeFilter').value.toLowerCase();

            let visibleCount = 0;

            document.querySelectorAll('.user-row').forEach(row => {
                // Only filter visible rows (based on current tab)
                if (row.style.display === 'none') return;

                const text = row.textContent.toLowerCase();
                const roleText = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                const collegeText = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';

                const matchesSearch = text.includes(search);
                const matchesRole = role === '' || roleText.includes(role);
                const matchesCollege = college === '' || collegeText.includes(college);

                const visible = matchesSearch && matchesRole && matchesCollege;

                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            // Update visible count
            document.getElementById('visibleCount').textContent = visibleCount;
            document.getElementById('visibleCountBottom').textContent = `${visibleCount} users`;
        }

        // ========================
        // Event Listeners
        // ========================
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial tab based on URL
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'all';
            switchTab(tab);

            // Attach filter listeners
            const searchInput = document.getElementById('searchUsers');
            const roleFilter = document.getElementById('roleFilter');
            const collegeFilter = document.getElementById('collegeFilter');

            if (searchInput) searchInput.addEventListener('input', filterTable);
            if (roleFilter) roleFilter.addEventListener('change', filterTable);
            if (collegeFilter) collegeFilter.addEventListener('change', filterTable);

            // Close modals on click outside
            window.addEventListener('click', e => {
                if (e.target.id.includes('Modal') && e.target.classList.contains('fixed')) {
                    e.target.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            });

            // Close modals on ESC key
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.fixed.inset-0').forEach(m => {
                        if (!m.classList.contains('hidden')) {
                            m.classList.add('hidden');
                        }
                    });
                    document.body.style.overflow = 'auto';
                }
            });

            console.log('✅ All event listeners attached');
        });
    </script>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>