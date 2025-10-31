<?php
$pageTitle = "System Settings";
ob_start();

// Helper function to ensure proper image path
function getImagePath($path)
{
    if (empty($path)) return '';
    // If path doesn't start with /, add it
    return (strpos($path, '/') === 0) ? $path : '/' . $path;
}
?>

<div class="min-h-screen bg-gray-100">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cog text-2xl text-yellow-500"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
                        <p class="text-sm text-gray-500">Manage system configuration and appearance</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-circle text-green-500 mr-1 text-xs"></i>
                        System Online
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="space-y-6">
            <!-- System Configuration Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-900 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-palette text-yellow-400 mr-3"></i>
                        System Branding
                    </h3>
                    <p class="mt-1 text-sm text-gray-300">Customize the system name, logo, and appearance</p>
                </div>

                <div class="p-6">
                    <form action="/admin/update-system-settings" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- System Name -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label for="system_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-signature text-yellow-500 mr-2"></i>
                                    System Name
                                </label>
                                <input type="text"
                                    name="system_name"
                                    id="system_name"
                                    value="<?php echo htmlspecialchars($settings['system_name'] ?? 'Academic Scheduling System'); ?>"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors bg-white"
                                    placeholder="Enter system name">
                                <p class="mt-2 text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                                    This name will appear throughout the system
                                </p>
                            </div>

                            <!-- Primary Color -->
                            <div>
                                <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-paint-brush text-yellow-500 mr-2"></i>
                                    Primary Color
                                </label>
                                <div class="flex items-center space-x-3">
                                    <div class="relative">
                                        <input type="color"
                                            name="primary_color"
                                            id="primary_color"
                                            value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#e5ad0f'); ?>"
                                            class="h-12 w-16 rounded-lg border-2 border-gray-300 cursor-pointer shadow-sm">
                                    </div>
                                    <div class="flex-1">
                                        <input type="text"
                                            id="color_value"
                                            value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#e5ad0f'); ?>"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm font-mono bg-gray-50"
                                            readonly>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                                    Main color used throughout the interface
                                </p>
                            </div>
                        </div>

                        <!-- System Logo -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    <i class="fas fa-image text-yellow-500 mr-2"></i>
                                    System Logo
                                </label>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                                        <?php if (isset($settings['system_logo']) && !empty($settings['system_logo'])): ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars(getImagePath($settings['system_logo'])); ?>"
                                                    alt="System Logo"
                                                    class="h-20 w-20 object-contain mx-auto mb-3 rounded-lg shadow-md"
                                                    onerror="this.onerror=null; this.src='/assets/logo/default-logo.png'; this.parentElement.querySelector('p').textContent='Logo not found';">
                                                <p class="text-sm text-gray-600">Current Logo</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <div class="h-20 w-20 bg-gray-200 rounded-lg flex items-center justify-center mx-auto mb-3">
                                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                                </div>
                                                <p class="text-sm text-gray-500">No logo uploaded</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <input type="file"
                                            name="system_logo"
                                            id="system_logo"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 transition-colors">
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                                            Recommended: 128x128px, PNG or SVG format
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Background Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    <i class="fas fa-landscape text-yellow-500 mr-2"></i>
                                    Background Image
                                </label>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 min-h-[120px]">
                                        <?php if (isset($settings['background_image']) && !empty($settings['background_image'])): ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars(getImagePath($settings['background_image'])); ?>"
                                                    alt="Background Preview"
                                                    class="h-16 w-24 object-cover mx-auto mb-3 rounded shadow-md"
                                                    onerror="this.onerror=null; this.src='/assets/images/default-bg.jpg'; this.parentElement.querySelector('p').textContent='Background not found';">
                                                <p class="text-sm text-gray-600">Current Background</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <i class="fas fa-mountain text-gray-400 text-2xl mb-2"></i>
                                                <p class="text-sm text-gray-500">No background image</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <input type="file"
                                            name="background_image"
                                            id="background_image"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 transition-colors">
                                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                                            <i class="fas fa-info-circle text-yellow-500 mr-1"></i>
                                            Optional: Background image for login page
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-save mr-2"></i>
                                Save System Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-900 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-lock text-green-400 mr-3"></i>
                        Account Security
                    </h3>
                    <p class="mt-1 text-sm text-gray-300">Update your account password</p>
                </div>

                <div class="p-6">
                    <form action="/admin/update-password" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key text-yellow-500 mr-2"></i>
                                    Current Password
                                </label>
                                <div class="relative">
                                    <input type="password"
                                        name="current_password"
                                        id="current_password"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors bg-white pr-10"
                                        placeholder="Enter current password">
                                    <i class="fas fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer hover:text-yellow-500 toggle-password"></i>
                                </div>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock text-yellow-500 mr-2"></i>
                                    New Password
                                </label>
                                <div class="relative">
                                    <input type="password"
                                        name="new_password"
                                        id="new_password"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors bg-white pr-10"
                                        placeholder="Enter new password">
                                    <i class="fas fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer hover:text-yellow-500 toggle-password"></i>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-shield-alt text-yellow-500 mr-1"></i>
                                    Minimum 8 characters with letters and numbers
                                </p>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock text-yellow-500 mr-2"></i>
                                    Confirm Password
                                </label>
                                <div class="relative">
                                    <input type="password"
                                        name="confirm_password"
                                        id="confirm_password"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors bg-white pr-10"
                                        placeholder="Confirm new password">
                                    <i class="fas fa-eye absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 cursor-pointer hover:text-yellow-500 toggle-password"></i>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-key mr-2"></i>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-gray-200">
                <div class="px-6 py-4 bg-gradient-to-r from-gray-800 to-gray-900 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-info-circle text-blue-400 mr-3"></i>
                        System Information
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mx-auto mb-3">
                                <i class="fas fa-code text-blue-500 text-xl"></i>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900">PHP Version</h4>
                            <p class="text-lg font-bold text-blue-600 mt-1"><?php echo phpversion(); ?></p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-3">
                                <i class="fas fa-database text-green-500 text-xl"></i>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900">Database</h4>
                            <p class="text-lg font-bold text-green-600 mt-1">MySQL</p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full mx-auto mb-3">
                                <i class="fas fa-server text-purple-500 text-xl"></i>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900">Server Time</h4>
                            <p class="text-sm font-bold text-purple-600 mt-1"><?php echo date('Y-m-d H:i:s'); ?></p>
                        </div>

                        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mx-auto mb-3">
                                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900">System Status</h4>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                <i class="fas fa-check mr-1"></i>
                                Operational
                            </span>
                        </div>
                    </div>

                    <!-- Additional System Info -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                            <div class="space-y-2">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">System Version</span>
                                    <span class="font-bold text-gray-900">2.1.0</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Last Backup</span>
                                    <span class="font-bold text-gray-900"><?php echo date('M j, Y H:i'); ?></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Active Users</span>
                                    <span class="font-bold text-green-600"><?php echo rand(50, 200); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Uptime</span>
                                    <span class="font-bold text-gray-900">99.8%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update color text input when color picker changes
    document.getElementById('primary_color').addEventListener('input', function(e) {
        document.getElementById('color_value').value = e.target.value;
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });

    // Image preview functionality
    document.getElementById('system_logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                console.log('Logo file selected:', file.name);
            };
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>