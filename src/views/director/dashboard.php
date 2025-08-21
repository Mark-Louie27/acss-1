<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }
        }

        .card {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <!-- Main Content -->
    <div class=" p-6 min-h-screen">
        <!-- Mobile Menu Toggle -->
        <button id="menuToggle" class="md:hidden fixed top-4 left-4 z-50 bg-indigo-600 text-white p-2 rounded">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <!-- Header -->
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($data['title']); ?></h1>
            <p class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?> | Last Updated: <?php echo htmlspecialchars($data['current_time']); ?></p>
            <?php if ($data['has_db_error']): ?>
                <p class="mt-2 text-red-600">Warning: Some data could not be loaded due to a database issue. Please contact support if this persists.</p>
            <?php endif; ?>
        </header>

        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- User Info Card -->
            <div class="card p-4">
                <h3 class="text-lg font-semibold text-gray-700">User Information</h3>
                <p class="mt-2 text-gray-600">Name: <?php echo htmlspecialchars($data['user']['first_name']); ?></p>
                <p class="text-gray-600">Email: <?php echo htmlspecialchars($data['user']['email']); ?></p>
                <p class="text-gray-600">Department ID: <?php echo htmlspecialchars($departmentId ?? 'N/A'); ?></p>
            </div>

            <!-- Current Semester Card -->
            <div class="card p-4">
                <h3 class="text-lg font-semibold text-gray-700">Current Semester</h3>
                <p class="mt-2 text-gray-600">Semester: <?php echo htmlspecialchars($data['semester']['semester_name']); ?></p>
                <p class="text-gray-600">ID: <?php echo htmlspecialchars($data['semester']['id'] ?? 'N/A'); ?></p>
            </div>

            <!-- Pending Approvals Card -->
            <div class="card p-4">
                <h3 class="text-lg font-semibold text-gray-700">Pending Approvals</h3>
                <p class="mt-2 text-2xl font-bold text-indigo-600"><?php echo htmlspecialchars($data['pending_approvals']); ?></p>
                <p class="text-gray-600">curricula awaiting review</p>
            </div>

            <!-- Class Schedules Card -->
            <div class="card p-4 col-span-1 md:col-span-2 lg:col-span-3">
                <h3 class="text-lg font-semibold text-gray-700">Class Schedules</h3>
                <?php if (!empty($data['schedules'])): ?>
                    <table class="mt-2 w-full text-gray-600">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-2 text-left">Course</th>
                                <th class="p-2 text-left">Room</th>
                                <th class="p-2 text-left">Faculty</th>
                                <th class="p-2 text-left">Day</th>
                                <th class="p-2 text-left">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['schedules'] as $schedule): ?>
                                <tr>
                                    <td class="p-2"><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($schedule['room_number']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars(date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="mt-2 text-gray-600">No schedules available for this semester.</p>
                <?php endif; ?>
            </div>

            <!-- Schedule Deadline Card -->
            <div class="card p-4 col-span-1 md:col-span-2 lg:col-span-3">
                <h3 class="text-lg font-semibold text-gray-700">Schedule Deadline</h3>
                <p class="mt-2 text-gray-600">Current Deadline: <?php echo $data['deadline'] ? htmlspecialchars($data['deadline']) : 'Not set'; ?></p>
                <a href="/director/set-schedule-deadline" class="mt-4 inline-block bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Set/Update Deadline</a>
            </div>
        </div>
    </div>

    <!-- JavaScript for Mobile Menu -->
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>