<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Program Chair Dashboard</title>
    <link rel="stylesheet" href="/css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
        }

        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: opacity 300ms, transform 300ms;
        }

        .modal-exit {
            opacity: 1;
            transform: scale(1);
        }

        .modal-exit-active {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 300ms, transform 300ms;
        }

        .modal-overlay {
            backdrop-filter: blur(4px);
        }

        .input-field {
            height: 40px;
            border: 1px solid #e2e8f0;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .input-field:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2);
            outline: none;
        }

        .input-label {
            font-weight: 500;
            margin-bottom: 6px;
            color: #374151;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        .required-indicator {
            color: #ef4444;
            margin-left: 2px;
        }

        .file-input-wrapper {
            border: 1px dashed #d1d5db;
            padding: 12px;
            border-radius: 8px;
            background-color: #f9fafb;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            background-color: #f3f4f6;
            border-color: #9ca3af;
        }

        .btn {
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .primary-btn {
            background-color: #d97706;
            color: white;
        }

        .primary-btn:hover {
            background-color: #b45309;
        }

        .cancel-btn {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .cancel-btn:hover {
            background-color: #e5e7eb;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .hover-scale {
            transition: transform 200ms ease-in-out;
        }

        .hover-scale:hover {
            transform: scale(1.02);
        }

        .input-focus {
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .input-focus:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header Section -->
        <header class="bg-white rounded-2xl shadow-lg p-6 mb-8 flex flex-col sm:flex-row items-center justify-between hover-scale">
            <div class="flex items-center mb-4 sm:mb-0">
                <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mr-4 overflow-hidden border-4 border-amber-500">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-3xl text-gray-600">👤</span>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="text-sm font-medium text-amber-600"><?php echo htmlspecialchars($user['role_name'] ?? 'Program Chair', ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['department_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($user['college_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <button id="editProfileBtn" class="bg-amber-500 text-white px-6 py-2 rounded-full flex items-center hover:bg-amber-600 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12h-5m0 0H9m6 0h1m-3 4h2m-7 0h2M5 5h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                </svg>
                Edit Profile
            </button>
        </header>

        <!-- Main Content -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Stats Card -->
            <section class="bg-white rounded-2xl shadow-lg p-6 lg:col-span-1 hover-scale">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Quick Stats
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                        <p class="text-sm text-gray-600">Faculty</p>
                        <p class="text-xl font-bold text-amber-500"><?php echo htmlspecialchars($facultyCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                        <p class="text-sm text-gray-600">Courses</p>
                        <p class="text-xl font-bold text-amber-500"><?php echo htmlspecialchars($coursesCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                        <p class="text-sm text-gray-600">Pending Applicants</p>
                        <p class="text-xl font-bold text-amber-500"><?php echo htmlspecialchars($pendingApplicantsCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-200">
                        <p class="text-sm text-gray-600">Semester</p>
                        <p class="text-xl font-bold text-amber-500"><?php echo htmlspecialchars($currentSemester ?? '2nd', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>

            <!-- Personal Information Card -->
            <section class="bg-white rounded-2xl shadow-lg p-6 lg:col-span-2 hover-scale">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Personal Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">First Name</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Middle Name</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Last Name</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Suffix</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email Address</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Phone Number</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>

            <!-- Department Information Card -->
            <section class="bg-white rounded-2xl shadow-lg p-6 lg:col-span-1 hover-scale">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10H3v7h1v-7z" />
                    </svg>
                    Department Information
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Employee ID</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['employee_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Department</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['department_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">College</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['college_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">College Code</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['college_id'] ? ($user['college_code'] ?? 'CCIT') : '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Role</p>
                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['role_name'] ?? 'Chair', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>

            <!-- Recent Activity Card -->
            <section class="bg-white rounded-2xl shadow-lg p-6 lg:col-span-2 hover-scale">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent Activity
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823.922-4" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-gray-600">Last Login</p>
                            <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($lastLogin ?? 'January 1, 1970, 1:00 am', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm text-gray-600">Profile Update</p>
                            <p class="text-sm font-medium text-gray-800">Last updated on <?php echo date('F d, Y', strtotime($user['updated_at'] ?? '2025-04-30')); ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Messages -->
        <?php if (isset($success)): ?>
            <div class="mt-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg">
                <p class="font-bold">Success</p>
                <p><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="mt-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg">
                <p class="font-bold">Error</p>
                <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-50 modal-overlay hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-2xl modal-content">
                <h3 class="modal-title">Edit Profile</h3>

                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div class="form-group">
                            <label for="first_name" class="block input-label">
                                First Name <span class="required-indicator">*</span>
                            </label>
                            <input type="text" name="first_name" id="first_name"
                                value="<?php echo htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Middle Name -->
                        <div class="form-group">
                            <label for="middle_name" class="block input-label">
                                Middle Name
                            </label>
                            <input type="text" name="middle_name" id="middle_name"
                                value="<?php echo htmlspecialchars($user['middle_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Last Name -->
                        <div class="form-group">
                            <label for="last_name" class="block input-label">
                                Last Name <span class="required-indicator">*</span>
                            </label>
                            <input type="text" name="last_name" id="last_name"
                                value="<?php echo htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Suffix -->
                        <div class="form-group">
                            <label for="suffix" class="block input-label">
                                Suffix
                            </label>
                            <input type="text" name="suffix" id="suffix"
                                value="<?php echo htmlspecialchars($user['suffix'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Email Address -->
                        <div class="form-group md:col-span-2">
                            <label for="email" class="block input-label">
                                Email Address <span class="required-indicator">*</span>
                            </label>
                            <input type="email" name="email" id="email"
                                value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Phone Number -->
                        <div class="form-group md:col-span-2">
                            <label for="phone" class="block input-label">
                                Phone Number
                            </label>
                            <input type="text" name="phone" id="phone"
                                value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                pattern="\d{10,12}" placeholder="10-12 digits"
                                class="w-full input-field px-4 rounded-lg input-focus">
                        </div>

                        <!-- Profile Picture -->
                        <div class="form-group md:col-span-2">
                            <label for="profile_picture" class="block input-label">
                                Profile Picture
                            </label>
                            <div class="file-input-wrapper">
                                <input type="file" name="profile_picture" id="profile_picture"
                                    accept="image/jpeg,image/png,image/gif"
                                    class="w-full text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                                <p class="text-xs text-gray-500 mt-2">Accepted formats: JPEG, PNG, GIF</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 mt-8 pt-5 border-t border-gray-100">
                        <button type="button" id="closeModalBtn"
                            class="btn cancel-btn">
                            Cancel
                        </button>
                        <button type="submit"
                            class="btn primary-btn">
                            Save Changes
                        </button>
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
            const modalContent = editProfileModal.querySelector('.max-w-lg');

            function openModal() {
                editProfileModal.classList.remove('hidden');
                modalContent.classList.add('modal-enter-active');
                modalContent.classList.remove('modal-enter');
            }

            function closeModal() {
                modalContent.classList.add('modal-exit-active');
                modalContent.classList.remove('modal-exit');
                setTimeout(() => {
                    editProfileModal.classList.add('hidden');
                    modalContent.classList.remove('modal-exit-active');
                }, 300);
            }

            editProfileBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);

            window.addEventListener('click', function(event) {
                if (event.target === editProfileModal) {
                    closeModal();
                }
            });

            // Form validation feedback
            const inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('invalid', function() {
                    input.classList.add('border-red-500');
                });
                input.addEventListener('input', function() {
                    input.classList.remove('border-red-500');
                });
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>