<?php
ob_start();
?>

<!DOCTYPE html>
<html lang=" en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - PRMSU Faculty</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        :root {
            --gold-primary: #DA9100;
            --gold-secondary: #FCC201;
            --gold-light: #FFEEAA;
            --gold-dark: #B8860B;
        }

        body {
            background-color: #f8fafc;
        }

        .gold-gradient {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-primary));
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .flash-message {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 0 3px rgba(218, 145, 0, 0.1);
        }

        .settings-tab {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .settings-tab.active {
            border-bottom: 3px solid var(--gold-primary);
            color: var(--gold-dark);
            font-weight: 600;
        }

        .settings-section {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .settings-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">
    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="flash-message mb-6 p-4 rounded-lg <?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas <?php echo $_SESSION['flash']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['flash']['message']); ?></span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold gold-gradient-text mb-2">
                    <i class="fas fa-cog mr-3"></i>Account Settings
                </h1>
                <p class="text-gray-600">Manage your account security and preferences</p>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button class="settings-tab active px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="security">
                            <i class="fas fa-shield-alt mr-2"></i>Security
                        </button>
                        <button class="settings-tab px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="profile">
                            <i class="fas fa-user mr-2"></i>Profile Information
                        </button>
                        <button class="settings-tab px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="activity">
                            <i class="fas fa-history mr-2"></i>Activity Log
                        </button>
                        <button class="settings-tab px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="account">
                            <i class="fas fa-info-circle mr-2"></i>Account Info
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Security Section -->
                    <div id="security" class="settings-section active">
                        <!-- Change Password -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold gold-gradient-text mb-4">
                                <i class="fas fa-lock mr-2"></i>Change Password
                            </h3>

                            <div id="password-alert" class="hidden mb-4 p-4 rounded-lg"></div>

                            <form id="passwordForm" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <!-- Current Password -->
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-key mr-2"></i>Current Password
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="password"
                                            id="current_password"
                                            name="current_password"
                                            required
                                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                            placeholder="Enter your current password">
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-2"></i>New Password
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="password"
                                            id="new_password"
                                            name="new_password"
                                            required
                                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                            placeholder="Enter your new password"
                                            minlength="8">
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <div class="password-strength-bar bg-gray-200" id="strength-bar"></div>
                                        <p class="text-sm text-gray-500 mt-1" id="strength-text">Must be at least 8 characters long</p>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-lock mr-2"></i>Confirm New Password
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            required
                                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                            placeholder="Confirm your new password"
                                            minlength="8">
                                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="pt-4">
                                    <button
                                        type="submit"
                                        class="btn-primary px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition">
                                        <i class="fas fa-save mr-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Security Tips -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-shield-alt mr-2"></i>Security Best Practices
                            </h4>
                            <ul class="text-yellow-700 text-sm space-y-1">
                                <li>• Use a strong, unique password that you don't use elsewhere</li>
                                <li>• Include a mix of uppercase, lowercase, numbers, and symbols</li>
                                <li>• Avoid using personal information in your password</li>
                                <li>• Change your password regularly (every 3-6 months)</li>
                                <li>• Never share your password with anyone</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Profile Information Section -->
                    <div id="profile" class="settings-section">
                        <h3 class="text-xl font-semibold gold-gradient-text mb-4">
                            <i class="fas fa-user mr-2"></i>Profile Information
                        </h3>

                        <div id="profile-alert" class="hidden mb-4 p-4 rounded-lg"></div>

                        <!-- Email Update -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Email Address</h4>
                            <form id="emailForm" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-envelope mr-2"></i>New Email Address
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                        placeholder="your.email@prmsu.edu.ph">
                                </div>

                                <div>
                                    <label for="email_password" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-key mr-2"></i>Confirm with Password
                                    </label>
                                    <input
                                        type="password"
                                        id="email_password"
                                        name="password"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                        placeholder="Enter your password to confirm">
                                </div>

                                <button
                                    type="submit"
                                    class="btn-primary px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition">
                                    <i class="fas fa-save mr-2"></i>Update Email
                                </button>
                            </form>
                        </div>

                        <!-- Phone Update -->
                        <div class="mb-8 pt-8 border-t border-gray-200">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Phone Number</h4>
                            <form id="phoneForm" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-phone mr-2"></i>Phone Number
                                    </label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                        pattern="[0-9]{10,15}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg form-input focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition"
                                        placeholder="09123456789">
                                    <p class="mt-1 text-sm text-gray-500">10-15 digits only</p>
                                </div>

                                <button
                                    type="submit"
                                    class="btn-primary px-6 py-3 rounded-lg font-semibold shadow-md hover:shadow-lg transition">
                                    <i class="fas fa-save mr-2"></i>Update Phone
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Activity Log Section -->
                    <div id="activity" class="settings-section">
                        <h3 class="text-xl font-semibold gold-gradient-text mb-4">
                            <i class="fas fa-history mr-2"></i>Recent Activity
                        </h3>

                        <?php if (!empty($login_history) && is_array($login_history)): ?>
                            <div class="space-y-3">
                                <?php foreach ($login_history as $log): ?>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                                <i class="fas fa-sign-in-alt text-blue-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($log['action_type'] ?? 'Activity'); ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($log['action_description'] ?? ''); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">
                                                <?php
                                                $date = new DateTime($log['created_at']);
                                                echo $date->format('M d, Y');
                                                ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo $date->format('h:i A'); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-4xl mb-3"></i>
                                <p>No recent activity to display</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Account Info Section -->
                    <div id="account" class="settings-section">
                        <h3 class="text-xl font-semibold gold-gradient-text mb-4">
                            <i class="fas fa-info-circle mr-2"></i>Account Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Account Created</p>
                                <p class="font-semibold text-gray-800">
                                    <?php
                                    if (isset($account_info['created_at'])) {
                                        $date = new DateTime($account_info['created_at']);
                                        echo $date->format('F d, Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Last Updated</p>
                                <p class="font-semibold text-gray-800">
                                    <?php
                                    if (isset($account_info['updated_at'])) {
                                        $date = new DateTime($account_info['updated_at']);
                                        echo $date->format('F d, Y');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">User ID</p>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['user_id'] ?? 'N/A'); ?></p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Username</p>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Email</p>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                            </div>

                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">Phone</p>
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <a
                            href="/director/dashboard"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-100 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab Switching
        document.querySelectorAll('.settings-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and sections
                document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));

                // Add active class to clicked tab and corresponding section
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength indicator
        const newPasswordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = [];

                if (password.length >= 8) {
                    strength++;
                } else {
                    feedback.push('at least 8 characters');
                }

                if (/[A-Z]/.test(password)) {
                    strength++;
                } else {
                    feedback.push('uppercase letter');
                }

                if (/[a-z]/.test(password)) {
                    strength++;
                } else {
                    feedback.push('lowercase letter');
                }

                if (/[0-9]/.test(password)) {
                    strength++;
                } else {
                    feedback.push('number');
                }

                if (/[^A-Za-z0-9]/.test(password)) {
                    strength++;
                } else {
                    feedback.push('special character');
                }

                const strengthLevels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
                const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500', 'bg-green-600'];
                const textColors = ['text-red-500', 'text-orange-500', 'text-yellow-500', 'text-blue-500', 'text-green-500', 'text-green-600'];

                strengthBar.className = `password-strength-bar ${strengthColors[strength]}`;
                strengthBar.style.width = `${(strength / 5) * 100}%`;

                if (password.length === 0) {
                    strengthText.textContent = 'Must be at least 8 characters long';
                    strengthText.className = 'text-sm text-gray-500 mt-1';
                    strengthBar.style.width = '0%';
                } else if (feedback.length > 0) {
                    strengthText.textContent = `Add: ${feedback.join(', ')}`;
                    strengthText.className = `text-sm ${textColors[strength]} mt-1 font-medium`;
                } else {
                    strengthText.textContent = `Password strength: ${strengthLevels[strength]}`;
                    strengthText.className = `text-sm ${textColors[strength]} mt-1 font-medium`;
                }
            });
        }

        // Password Form Submission
        document.getElementById('passwordForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const alertDiv = document.getElementById('password-alert');

            if (newPassword !== confirmPassword) {
                showAlert(alertDiv, 'New password and confirmation do not match.', 'error');
                return;
            }

            if (newPassword.length < 8) {
                showAlert(alertDiv, 'New password must be at least 8 characters long.', 'error');
                return;
            }

            const formData = new FormData(this);

            try {
                const response = await fetch('/director/updatePassword', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(alertDiv, result.message, 'success');
                    this.reset();
                    strengthBar.style.width = '0%';
                    strengthText.textContent = 'Must be at least 8 characters long';
                    strengthText.className = 'text-sm text-gray-500 mt-1';
                } else {
                    showAlert(alertDiv, result.message, 'error');
                }
            } catch (error) {
                showAlert(alertDiv, 'An error occurred. Please try again.', 'error');
            }
        });

        // Email Form Submission
        document.getElementById('emailForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const alertDiv = document.getElementById('profile-alert');

            try {
                const response = await fetch('/director/updateEmail', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(alertDiv, result.message, 'success');
                    document.getElementById('email_password').value = '';
                } else {
                    showAlert(alertDiv, result.message, 'error');
                }
            } catch (error) {
                showAlert(alertDiv, 'An error occurred. Please try again.', 'error');
            }
        });

        // Phone Form Submission
        document.getElementById('phoneForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const alertDiv = document.getElementById('profile-alert');

            try {
                const response = await fetch('/director/updatePhone', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(alertDiv, result.message, 'success');
                } else {
                    showAlert(alertDiv, result.message, 'error');
                }
            } catch (error) {
                showAlert(alertDiv, 'An error occurred. Please try again.', 'error');
            }
        });

        // Show Alert Helper Function
        function showAlert(element, message, type) {
            element.className = `mb-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`;
            element.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            element.classList.remove('hidden');

            setTimeout(() => {
                element.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>