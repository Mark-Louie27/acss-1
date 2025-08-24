<?php
// Default values for safety
$teachingLoad = isset($teachingLoad) ? $teachingLoad : 0;
$pendingRequests = isset($pendingRequests) ? $pendingRequests : 0;
$recentSchedules = isset($recentSchedules) ? $recentSchedules : [];
$scheduleDistJson = isset($scheduleDistJson) ? $scheduleDistJson : json_encode([0, 0, 0, 0, 0, 0]);
$departmentName = isset($departmentName) ? $departmentName : 'Department';
$error = isset($error) ? $error : '';
$success = isset($success) ? $success : '';

ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRMSU Scheduling System - Faculty</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            50: '#FEF9E7',
                            100: '#FCF3CF',
                            200: '#F9E79F',
                            300: '#F7DC6F',
                            400: '#F5D33F',
                            500: '#D4AF37',
                            600: '#B8860B',
                            700: '#9A7209',
                            800: '#7C5E08',
                            900: '#5E4506',
                        },
                        gray: {
                            50: '#F9FAFB',
                            100: '#F3F4F6',
                            200: '#E5E7EB',
                            300: '#D1D5DB',
                            400: '#9CA3AF',
                            500: '#6B7280',
                            600: '#4B5563',
                            700: '#374151',
                            800: '#1F2937',
                            900: '#111827',
                        }
                    },
                    fontFamily: {
                        'sans': ['Roboto', 'sans-serif'],
                        'heading': ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 4px 6px rgba(0, 0, 0, 0.1)',
                        'hover': '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 20px rgba(0, 0, 0, 0.05), 0 6px 6px rgba(0, 0, 0, 0.03)',
                    }
                },
            },
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .sidebar {
            background: linear-gradient(to bottom, #1F2937, #111827);
        }
    </style>
</head>

<body class="bg-gray-100">


    <!-- Main content -->
    <div class="flex flex-col min-h-screen">
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>
        </div>

        <!-- Main Header Section with Gold Accent -->
        <div class="bg-gray-800 text-white rounded-xl p-6 mb-8 shadow-lg relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-yellow-600"></div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">PRMSU Scheduling System</h1>
                    <h3 class="text-2xl font-bold text-white">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h3>
                    <?php if (isset($departmentName) && !empty($departmentName)): ?>
                        <p class="text-gray-300 mt-2"><?php echo htmlspecialchars($departmentName); ?></p>
                    <?php endif; ?>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <span class="text-sm bg-gray-700 px-3 py-1 rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <?php echo htmlspecialchars($semesterInfo, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="text-sm bg-yellow-600 px-3 py-1 rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Active Term
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center hover:shadow-lg transition-shadow duration-300 cursor-pointer" onclick="window.location.href='/faculty/schedule'">
                <div class="p-3 rounded-full bg-gold-100 text-gold-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-600">Teaching Load</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($teachingLoad); ?></p>
                    <p class="text-sm text-gray-500">Assigned schedules</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 flex items-center hover:shadow-lg transition-shadow duration-300 cursor-pointer" onclick="window.location.href='/faculty/schedule/requests'">
                <div class="p-3 rounded-full bg-gold-100 text-gold-600 mr-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-600">Pending Requests</h3>
                    <p class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($pendingRequests); ?></p>
                    <p class="text-sm text-gray-500">Schedule requests</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule Distribution</h3>
                <div class="h-64">
                    <canvas id="scheduleChart" width="400" height="400"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="/faculty/schedule/request" class="bg-gold-600 text-white px-4 py-3 rounded-md hover:bg-gold-700 transition duration-300 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Submit Request
                    </a>
                    <a href="/faculty/schedule" class="bg-white border border-gray-300 text-gray-700 px-4 py-3 rounded-md hover:bg-gray-50 transition duration-300 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        View Schedule
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Schedules</h3>
                <p class="text-sm text-gray-500 mt-1">Your recently assigned schedules</p>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($recentSchedules)): ?>
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No schedules</h3>
                        <p class="mt-1 text-sm text-gray-500">You have no assigned schedules yet.</p>
                    </div>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day & Time</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentSchedules as $schedule): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['course_code']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['room_name'] ?? 'Online'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['day_of_week']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($schedule['start_time'] . ' - ' . $schedule['end_time']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $typeClasses = [
                                            'F2F' => 'bg-green-100 text-green-800',
                                            'Online' => 'bg-blue-100 text-blue-800',
                                            'Hybrid' => 'bg-purple-100 text-purple-800',
                                            'Asynchronous' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $typeClass = $typeClasses[$schedule['schedule_type']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $typeClass; ?>">
                                            <?php echo htmlspecialchars($schedule['schedule_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</body>

</html>

<?php
// Capture the content and pass it to layout
$content = ob_get_clean();
require_once __DIR__ . '/../../views/faculty/layout.php';
?>