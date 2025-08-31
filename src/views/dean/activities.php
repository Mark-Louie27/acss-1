<?php

ob_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/custom.css">
    <style>
        /* Custom Colors */
        .bg-navy-600 {
            background-color: #1e3a8a;
        }

        .bg-navy-700 {
            background-color: #172554;
        }

        .bg-navy-800 {
            background-color: #111827;
        }

        .text-navy-200 {
            color: #e5e7eb;
        }

        .bg-gold-400 {
            background-color: #f59e0b;
        }

        .bg-gold-50 {
            background-color: #fefce8;
        }

        .text-gold-400 {
            color: #f59e0b;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes pulseSlow {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        .animate-pulse-slow {
            animation: pulseSlow 2s infinite;
        }

        /* Responsive Adjustments */
        @media (max-width: 640px) {
            .px-8 {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .text-2xl {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 font-sans antialiased min-h-screen">
    <div class="container mx-auto p-6 lg:p-8">

        <!-- Activity Logs Section -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl lg:text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent Activities
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-800 text-white rounded-t-xl">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $activitiesLimited = array_slice($activities ?? [], 0, 10);
                        if (empty($activitiesLimited)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No recent activities found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activitiesLimited as $activity): ?>
                                <tr class="hover:bg-gold-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($activity['department_name'] ?? 'Unknown'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['action_type'] ?? 'Unknown Action'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($activity['action_description'] ?? 'No description'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($activity['created_at']))); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (count($activities ?? []) > 10): ?>
                    <div class="mt-4 text-right">
                        <a href="?view=all&activities=1" class="text-gold-400 hover:text-gold-600 font-medium transition-colors duration-200">View All</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Add any future interactivity here if needed
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>
