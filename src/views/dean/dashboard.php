<?php
ob_start();
?>

<h2 class="text-3xl font-bold text-gray-600 mb-6">Dashboard</h2>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Faculty Card -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gold-400 card">
        <h3 class="text-lg font-semibold text-gray-600">Total Faculty</h3>
        <p class="text-3xl font-bold text-gold-400 mt-2"><?php echo $stats['total_faculty']; ?></p>
    </div>
    <!-- Classrooms Card -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gold-400 card">
        <h3 class="text-lg font-semibold text-gray-600">Total Classrooms</h3>
        <p class="text-3xl font-bold text-gold-400 mt-2"><?php echo $stats['total_classrooms']; ?></p>
    </div>
    <!-- Schedules Card -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gold-400 card">
        <h3 class="text-lg font-semibold text-gray-600">Total Schedules</h3>
        <p class="text-3xl font-bold text-gold-400 mt-2"><?php echo $stats['total_schedules']; ?></p>
    </div>
    <!-- Pending Approvals Card -->
    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gold-400 card">
        <h3 class="text-lg font-semibold text-gray-600">Pending Approvals</h3>
        <p class="text-3xl font-bold text-gold-400 mt-2"><?php echo $stats['pending_approvals']; ?></p>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white p-6 rounded-lg shadow-md card">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Quick Actions</h3>
    <div class="flex flex-wrap gap-4">
        <a href="/dean/curriculum" class="bg-gold-400 text-white px-4 py-2 rounded hover:bg-gold-500 btn">Review Curriculum</a>
        <a href="/dean/faculty" class="bg-gold-400 text-white px-4 py-2 rounded hover:bg-gold-500 btn">Manage Faculty</a>
        <a href="/dean/classroom" class="bg-gold-400 text-white px-4 py-2 rounded hover:bg-gold-500 btn">Approve Room Reservations</a>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>