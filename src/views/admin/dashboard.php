<?php
ob_start();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Admin Dashboard</h1>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Users</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($userCount, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Colleges</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($collegeCount, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Departments</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($departmentCount, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Faculty</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($facultyCount, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Schedules</h2>
        <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($scheduleCount, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>