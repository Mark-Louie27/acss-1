<?php
ob_start();
?>

<div class="p-6 bg-gray-100 min-h-screen font-sans">
    <!-- Profile Header -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 flex flex-col md:flex-row items-center justify-between transform hover:scale-[1.01] transition-transform duration-300">
        <div class="flex items-center mb-4 md:mb-0">
            <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mr-4 overflow-hidden border-4 border-yellow-500">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-4xl text-gray-600">👤</span>
                <?php endif; ?>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''); ?></h2>
                <p class="text-sm font-medium text-yellow-600">Program Chair</p>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['department_name'] ?? ''); ?></p>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['college_id'] ? 'College of Communications and Information Technology' : ''); ?></p>
            </div>
        </div>
        <button id="editProfileBtn" class="bg-yellow-500 text-white px-5 py-2 rounded-full flex items-center hover:bg-yellow-600 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L19.5 7.5l-1.414 1.414-4.95 4.95-3.536.707a1 1 0 01-1.121-1.121l.707-3.536 4.95-4.95z" />
            </svg>
            Edit Profile
        </button>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Statistics -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                    <p class="text-sm text-gray-600">Faculty</p>
                    <p class="text-2xl font-bold text-yellow-500"><?php echo htmlspecialchars($facultyCount ?? 0); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                    <p class="text-sm text-gray-600">Courses</p>
                    <p class="text-2xl font-bold text-yellow-500"><?php echo htmlspecialchars($coursesCount ?? 0); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-500"><?php echo htmlspecialchars($pendingApplicantsCount ?? 0); ?></p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                    <p class="text-sm text-gray-600">Semester</p>
                    <p class="text-2xl font-bold text-yellow-500"><?php echo $currentSemester ?? '2nd'; ?></p>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Personal Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">First Name</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Middle Name</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['middle_name'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Last Name</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Suffix</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['suffix'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Email Address</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Phone Number</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></p>
                </div>
            </div>
        </div>

        <!-- Department Information -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Department Information
            </h3>
            <div class="grid grid-cols-1 gap-2">
                <div>
                    <p class="text-sm text-gray-600">Employee ID</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['employee_id'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Department</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['department_name'] ?? ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">College</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['college_id'] ? 'College of Communications and Information Technology' : ''); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">College Code</p>
                    <p class="text-sm font-medium text-gray-800">CCIT</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Role</p>
                    <p class="text-sm font-medium text-gray-800">Chair</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Username</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Recent Activity
            </h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823.922-4" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm text-gray-600">Last Login</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($lastLogin ?? 'January 1, 1970, 1:00 am'); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm text-gray-600">Profile Update</p>
                        <p class="text-sm font-medium text-gray-800">Your profile was last updated on <?php echo date('F d, Y', strtotime($user['updated_at'] ?? '2025-04-30')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Security -->
        <div class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 transform hover:scale-[1.01] transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Account Security</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm text-gray-600">Last Login</p>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($lastLogin ?? 'January 1, 1970, 1:00 am'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Password</p>
                    <p class="text-sm font-medium text-gray-800">••••••••••</p>
                </div>
                <p class="text-sm font-medium text-green-600">Secure</p>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mt-6 rounded-lg" role="alert">
            <p class="font-bold">Success</p>
            <p><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mt-6 rounded-lg" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md transform scale-95 transition-transform duration-300">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Profile</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                    <input type="text" name="suffix" id="suffix" value="<?php echo htmlspecialchars($user['suffix'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-yellow-500 focus:border-yellow-500">
                </div>
                <div>
                    <label for="profile_picture" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="closeModalBtn" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-300 transition-colors duration-200">Cancel</button>
                    <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-full hover:bg-yellow-600 transition-colors duration-200">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editProfileBtn = document.getElementById('editProfileBtn');
        const editProfileModal = document.getElementById('editProfileModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        editProfileBtn.addEventListener('click', function() {
            editProfileModal.classList.remove('hidden');
            setTimeout(() => {
                editProfileModal.querySelector('.max-w-md').classList.remove('scale-95');
            }, 10);
        });

        closeModalBtn.addEventListener('click', function() {
            editProfileModal.querySelector('.max-w-md').classList.add('scale-95');
            setTimeout(() => {
                editProfileModal.classList.add('hidden');
            }, 300);
        });

        window.addEventListener('click', function(event) {
            if (event.target === editProfileModal) {
                editProfileModal.querySelector('.max-w-md').classList.add('scale-95');
                setTimeout(() => {
                    editProfileModal.classList.add('hidden');
                }, 300);
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>