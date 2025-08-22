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
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gold-primary': '#D4AF37',
                        'gold-light': '#F7E98E',
                        'gold-dark': '#B8860B',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- Main Content Area (accounting for sidebar) -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-green-800 font-medium"><?php echo htmlspecialchars($_SESSION['success']);
                                                                    unset($_SESSION['success']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                        <span class="text-red-800 font-medium"><?php echo htmlspecialchars($_SESSION['error']);
                                                                unset($_SESSION['error']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Current Deadline Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-gold-primary to-gold-dark p-6">
                            <div class="flex items-center text-white">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-clock text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">Current Status</h3>
                                    <p class="text-sm opacity-90">Schedule Deadline</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <?php if ($data['current_deadline']): ?>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-1">Deadline Date</p>
                                        <p class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars(date('M j, Y', strtotime($data['current_deadline']))); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars(date('g:i A', strtotime($data['current_deadline']))); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 mb-2">Time Remaining</p>
                                        <?php
                                        $currentTime = new DateTime();
                                        $deadlineTime = new DateTime($data['current_deadline']);
                                        $interval = $currentTime->diff($deadlineTime);

                                        if ($deadlineTime > $currentTime): ?>
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Active
                                                </span>
                                                <span class="text-sm text-gray-600">
                                                    <?php echo $interval->format('%a days, %h hours'); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Expired
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-calendar-times text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Deadline Set</h3>
                                    <p class="text-sm text-gray-500">Faculty can submit schedules without time restrictions</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Information Card -->
                    <div class="mt-6 bg-blue-50 rounded-xl border border-blue-200 p-6">
                        <h4 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Important Information
                        </h4>
                        <ul class="text-xs text-blue-800 space-y-2">
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mt-0.5 mr-2 text-xs"></i>
                                Faculty will receive notifications about the deadline
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mt-0.5 mr-2 text-xs"></i>
                                Submissions after deadline require director approval
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mt-0.5 mr-2 text-xs"></i>
                                You can update the deadline anytime
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-600 mt-0.5 mr-2 text-xs"></i>
                                System sends reminders 24 hours before deadline
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-calendar-alt text-gold-primary mr-3"></i>
                                Set New Deadline
                            </h3>
                            <p class="mt-1 text-sm text-gray-600">Choose when faculty schedule submissions will close</p>
                        </div>

                        <form method="POST" id="deadlineForm" class="p-6">
                            <!-- Quick Presets -->
                            <div class="mb-6">
                                <label class="text-sm font-medium text-gray-700 mb-3 block">Quick Presets</label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="presets">
                                    <button type="button" class="preset-btn" data-hours="24">
                                        <i class="fas fa-sun text-yellow-500 mb-1"></i>
                                        <div class="text-xs font-medium">Tomorrow</div>
                                    </button>
                                    <button type="button" class="preset-btn" data-hours="168">
                                        <i class="fas fa-calendar-week text-blue-500 mb-1"></i>
                                        <div class="text-xs font-medium">Next Week</div>
                                    </button>
                                    <button type="button" class="preset-btn" data-hours="336">
                                        <i class="fas fa-calendar-alt text-green-500 mb-1"></i>
                                        <div class="text-xs font-medium">2 Weeks</div>
                                    </button>
                                    <button type="button" class="preset-btn" data-hours="720">
                                        <i class="fas fa-calendar text-purple-500 mb-1"></i>
                                        <div class="text-xs font-medium">1 Month</div>
                                    </button>
                                </div>
                            </div>

                            <!-- Date & Time Input -->
                            <div class="mb-6">
                                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-2">
                                    Deadline Date & Time
                                </label>
                                <div class="relative">
                                    <input
                                        type="datetime-local"
                                        id="deadline"
                                        name="deadline"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-gold-primary focus:border-gold-primary transition-colors duration-200 text-gray-900"
                                        required
                                        min="<?php echo date('Y-m-d\TH:i'); ?>"
                                        value="<?php echo $data['current_deadline'] ? date('Y-m-d\TH:i', strtotime($data['current_deadline'])) : ''; ?>">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-calendar-alt text-gold-primary"></i>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Select a future date and time when submissions will close
                                </p>
                                <div id="deadline-feedback" class="mt-2 text-sm font-medium"></div>
                            </div>

                            <!-- Timezone Info -->
                            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <div class="flex items-center text-sm text-yellow-800">
                                    <i class="fas fa-globe text-yellow-600 mr-2"></i>
                                    <span>All times are in your local timezone (<?php echo date('T'); ?>)</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="button" onclick="window.history.back()" class="flex-1 sm:flex-none px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-primary transition-all duration-200">
                                    Cancel
                                </button>
                                <button type="submit" id="submitBtn" class="flex-1 px-6 py-3 bg-gradient-to-r from-gold-primary to-gold-dark text-white rounded-lg text-sm font-medium hover:from-gold-dark hover:to-gold-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-primary transition-all duration-200 flex items-center justify-center">
                                    <i class="fas fa-save mr-2"></i>
                                    <span class="btn-text">Set Deadline</span>
                                    <i class="fas fa-spinner fa-spin hidden ml-2" id="loadingIcon"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .preset-btn {
            @apply bg-gray-50 border border-gray-200 rounded-lg p-3 text-center hover:bg-gold-primary hover:text-white hover:border-gold-primary transition-all duration-200 cursor-pointer flex flex-col items-center;
        }

        .preset-btn:hover i {
            @apply text-white;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('deadlineForm');
            const submitBtn = document.getElementById('submitBtn');
            const loadingIcon = document.getElementById('loadingIcon');
            const btnText = submitBtn.querySelector('.btn-text');
            const deadlineInput = document.getElementById('deadline');

            // Preset buttons functionality
            document.getElementById('presets').addEventListener('click', function(e) {
                const btn = e.target.closest('.preset-btn');
                if (btn) {
                    const hours = parseInt(btn.dataset.hours);
                    const futureDate = new Date();
                    futureDate.setHours(futureDate.getHours() + hours);

                    const formattedDate = futureDate.toISOString().slice(0, 16);
                    deadlineInput.value = formattedDate;

                    // Trigger validation
                    deadlineInput.dispatchEvent(new Event('change'));

                    // Visual feedback
                    btn.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        btn.style.transform = '';
                    }, 150);
                }
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const deadlineValue = deadlineInput.value;

                if (!deadlineValue) {
                    e.preventDefault();
                    showAlert('Please select a deadline date and time.', 'error');
                    return;
                }

                const selectedDate = new Date(deadlineValue);
                const now = new Date();

                if (selectedDate <= now) {
                    e.preventDefault();
                    showAlert('Deadline must be in the future.', 'error');
                    return;
                }

                // Show loading state
                showLoadingState();
            });

            // Real-time validation feedback
            deadlineInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const now = new Date();
                const diffHours = (selectedDate - now) / (1000 * 60 * 60);

                let feedback = '';
                let feedbackClass = '';

                if (!this.value) {
                    feedback = '';
                } else if (selectedDate <= now) {
                    feedback = '⚠️ Deadline must be in the future';
                    feedbackClass = 'text-red-600';
                } else if (diffHours < 24) {
                    feedback = '⏰ Less than 24 hours from now';
                    feedbackClass = 'text-yellow-600';
                } else {
                    const days = Math.floor(diffHours / 24);
                    feedback = `✅ ${days} day${days !== 1 ? 's' : ''} from now`;
                    feedbackClass = 'text-green-600';
                }

                const feedbackEl = document.getElementById('deadline-feedback');
                feedbackEl.textContent = feedback;
                feedbackEl.className = `mt-2 text-sm font-medium ${feedbackClass}`;
            });

            function showLoadingState() {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                loadingIcon.classList.remove('hidden');
                btnText.textContent = 'Setting Deadline...';
            }

            function showAlert(message, type) {
                const alertDiv = document.createElement('div');
                const bgColor = type === 'error' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200';
                const textColor = type === 'error' ? 'text-red-800' : 'text-green-800';
                const iconColor = type === 'error' ? 'text-red-500' : 'text-green-500';
                const icon = type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle';

                alertDiv.className = `mb-6 ${bgColor} border rounded-lg p-4`;
                alertDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas ${icon} ${iconColor} mr-3"></i>
                        <span class="${textColor} font-medium">${message}</span>
                    </div>
                `;

                const container = document.querySelector('.max-w-4xl');
                container.insertBefore(alertDiv, container.firstChild);

                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.history.back();
                }
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });

            // Auto-hide existing alerts
            const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
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