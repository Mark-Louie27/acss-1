<?php
$pageTitle = "Admin Profile";
ob_start();
?>

<div class="min-h-screen bg-gray-100">
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <?php if ($user['profile_picture']): ?>
                            <img class="h-24 w-24 rounded-full object-cover" src="/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="h-24 w-24 rounded-full bg-gray-300 flex items-center justify-center text-2xl font-bold text-gray-600">
                                <?php echo htmlspecialchars(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-6">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name'] . ($user['suffix'] ? ', ' . $user['suffix'] : '')); ?>
                        </h1>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['role_name']); ?></p>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Personal Information</h2>
                        <dl class="mt-4 space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Username</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['username']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></dd>
                            </div>
                        </dl>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700">Academic Information</h2>
                        <dl class="mt-4 space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">College</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['college_name'] ?: 'Not assigned'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Department</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['department_name'] ?: 'Not assigned'); ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mt-8">
                    <a href="/admin/settings" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>