<?php
ob_start();

// Check for success/error messages from DeanController
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;

// Fetch user data
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $controller->db->prepare($query);
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch departments for the Dean's college
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$query = "SELECT department_id, department_name FROM departments WHERE college_id = :college_id ORDER BY department_name";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch college name
$query = "SELECT college_name FROM colleges WHERE college_id = :college_id";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$collegeName = $stmt->fetchColumn();

// Fetch quick stats with prepared statements
$departmentCount = 0;
$facultyCount = 0;
$courseCount = 0;

if ($collegeId) {
    // Department count
    $query = "SELECT COUNT(*) FROM departments WHERE college_id = :college_id";
    $stmt = $controller->db->prepare($query);
    $stmt->execute([':college_id' => $collegeId]);
    $departmentCount = $stmt->fetchColumn();

    // Faculty count (role_id = 3 for Department Instructor)
    $query = "SELECT COUNT(*) FROM users WHERE role_id = 3 AND college_id = :college_id";
    $stmt = $controller->db->prepare($query);
    $stmt->execute([':college_id' => $collegeId]);
    $facultyCount = $stmt->fetchColumn();

    // Course count (join with departments to get college_id)
    $query = "SELECT COUNT(*) FROM courses c JOIN departments d ON c.department_id = d.department_id WHERE d.college_id = :college_id";
    $stmt = $controller->db->prepare($query);
    $stmt->execute([':college_id' => $collegeId]);
    $courseCount = $stmt->fetchColumn();
}

// Fetch last login (assuming auth_logs table stores login history)
$query = "SELECT MAX(created_at) FROM auth_logs WHERE user_id = :user_id AND action = 'login_success'";
$stmt = $controller->db->prepare($query);
$stmt->execute([':user_id' => $userId]);
$lastLogin = $stmt->fetchColumn() ?: 'N/A';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Profile | ACSS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --gold: #D4AF37;
            --white: #FFFFFF;
            --gray-dark: #4B5563;
            --gray-light: #E5E7EB;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modal {
            transition: opacity 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease;
        }

        .input-focus {
            transition: all 0.2s ease;
        }

        .input-focus:focus {
            border-color: var(--gold);
            ring-color: var(--gold);
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
        }

        .btn-gold:hover {
            background-color: #b8972e;
        }

        .file-input-wrapper {
            border: 1px dashed var(--gray-light);
            padding: 12px;
            border-radius: 8px;
            background-color: #f9fafb;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            background-color: #f3f4f6;
            border-color: var(--gray-dark);
        }
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <header class="mb-8 slide-in-left">
            <h2 class="text-4xl font-bold text-gray-dark">Dean Profile</h2>
            <p class="text-gray-dark mt-2">Manage your personal and professional information</p>
        </header>

        <!-- Main Grid -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Overview Card -->
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2 fade-in" role="region" aria-label="Profile Overview">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Profile Picture -->
                    <div class="flex-shrink-0">
                        <?php if ($user['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover border-4 border-gold shadow-md" aria-label="Current profile picture">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gray-light flex items-center justify-center border-4 border-gold shadow-md" aria-label="Profile picture placeholder">
                                <i class="fas fa-user text-4xl text-gray-dark"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- User Info -->
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-semibold text-gray-dark">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ($user['suffix'] ? ' ' . $user['suffix'] : '')); ?>
                                </h3>
                                <p class="text-gold font-medium">Dean, <?php echo htmlspecialchars($collegeName ?: 'N/A'); ?></p>
                                <p class="text-gray-dark mt-2"><?php echo htmlspecialchars($user['email']); ?></p>
                                <p class="text-gray-dark"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                                <p class="text-gray-dark mt-1">Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
                                <p class="text-gray-dark">Department: <?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></p>
                            </div>
                            <button id="editProfileBtn" class="btn-gold px-5 py-2 rounded-lg shadow-md hover:shadow-lg flex items-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gold focus:ring-opacity-50">
                                <i class="fas fa-edit mr-2"></i> Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Card -->
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 fade-in" role="region" aria-label="Quick Stats">
                <h3 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-gold"></i> Quick Stats
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Departments</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($departmentCount); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Faculty</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($facultyCount); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Courses</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($courseCount); ?></p>
                    </div>
                </div>
            </section>

            <!-- Recent Activity Card -->
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-3 fade-in" role="region" aria-label="Recent Activity">
                <h3 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-clock mr-2 text-gold"></i> Recent Activity
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-sign-in-alt text-gold"></i>
                        </span>
                        <div>
                            <p class="text-sm text-gray-dark">Last Login</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($lastLogin); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-edit text-gold"></i>
                        </span>
                        <div>
                            <p class="text-sm text-gray-dark">Profile Update</p>
                            <p class="text-sm font-medium text-gray-dark">Last updated on <?php echo date('F d, Y', strtotime($user['updated_at'] ?? '2025-04-30')); ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Edit Profile</h3>
                    <button id="closeModalBtn"
                        class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200"
                        aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="profileForm" action="/dean/profile" method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employee ID -->
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-dark mb-1">Employee ID <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-id-badge text-gray-dark"></i>
                            </div>
                            <input type="text" id="employee_id" name="employee_id" required
                                value="<?php echo htmlspecialchars($user['employee_id']); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Employee ID is required.</p>
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-dark mb-1">Username <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                value="<?php echo htmlspecialchars($user['username']); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Username is required.</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-dark mb-1">Email <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-dark"></i>
                            </div>
                            <input type="email" id="email" name="email" required
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Valid email is required.</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-dark mb-1">Phone</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-dark"></i>
                            </div>
                            <input type="text" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                pattern="\d{10,12}" placeholder="10-12 digits"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Phone number must be 10-12 digits.</p>
                    </div>

                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-dark mb-1">First Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="first_name" name="first_name" required
                                value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">First name is required.</p>
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-dark mb-1">Middle Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="middle_name" name="middle_name"
                                value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-dark mb-1">Last Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="last_name" name="last_name" required
                                value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Last name is required.</p>
                    </div>

                    <!-- Suffix -->
                    <div>
                        <label for="suffix" class="block text-sm font-medium text-gray-dark mb-1">Suffix</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="suffix" name="suffix"
                                value="<?php echo htmlspecialchars($user['suffix'] ?? ''); ?>"
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>

                    <!-- Department -->
                    <div class="md:col-span-2">
                        <label for="department_id" class="block text-sm font-medium text-gray-dark mb-1">Department <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-building text-gray-dark"></i>
                            </div>
                            <select id="department_id" name="department_id" required
                                class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                                aria-required="true">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>" <?php echo $user['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Department is required.</p>
                    </div>

                    <!-- Profile Picture -->
                    <div class="md:col-span-2">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-dark mb-1">Profile Picture</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif"
                                class="w-full text-gray-dark file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-50 file:text-gray-dark hover:file:bg-gray-100">
                            <p class="text-xs text-gray-dark mt-2">Accepted formats: JPEG, PNG, GIF (max 2MB)</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="md:col-span-2 flex justify-end space-x-3 pt-4 border-t border-gray-light">
                        Britannica
                        <button type="button" id="cancelModalBtn"
                            class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toast Notifications
            <?php if ($success): ?>
                showToast('<?php echo $success; ?>', 'bg-green-500');
            <?php endif; ?>
            <?php if ($error): ?>
                showToast('<?php echo $error; ?>', 'bg-red-500');
            <?php endif; ?>

            function showToast(message, bgColor) {
                const toast = document.createElement('div');
                toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg`;
                toast.textContent = message;
                toast.setAttribute('role', 'alert');
                document.getElementById('toast-container').appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }

            // Modal Functions
            function openModal() {
                const modal = document.getElementById('editProfileModal');
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                const modal = document.getElementById('editProfileModal');
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    // Reset form validation
                    const form = modal.querySelector('form');
                    form.querySelectorAll('.error-message').forEach(msg => msg.classList.add('hidden'));
                    form.querySelectorAll('input, select').forEach(input => input.classList.remove('border-red-500'));
                }, 200);
            }

            // Event Listeners
            document.getElementById('editProfileBtn').addEventListener('click', openModal);
            document.getElementById('closeModalBtn').addEventListener('click', closeModal);
            document.getElementById('cancelModalBtn').addEventListener('click', closeModal);

            // Close modal on backdrop click
            document.getElementById('editProfileModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('editProfileModal')) closeModal();
            });

            // Close modal on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !document.getElementById('editProfileModal').classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Form Validation
            const form = document.getElementById('profileForm');
            form.addEventListener('submit', (e) => {
                let isValid = true;

                // Required fields
                ['employee_id', 'username', 'first_name', 'last_name', 'email', 'department_id'].forEach(id => {
                    const input = document.getElementById(id);
                    const errorMessage = input.nextElementSibling?.nextElementSibling || input.nextElementSibling;
                    if (!input.value.trim()) {
                        input.classList.add('border-red-500');
                        errorMessage?.classList.remove('hidden');
                        isValid = false;
                    } else {
                        input.classList.remove('border-red-500');
                        errorMessage?.classList.add('hidden');
                    }
                });

                // Email validation
                const emailInput = document.getElementById('email');
                const emailError = emailInput.nextElementSibling?.nextElementSibling;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    emailInput.classList.add('border-red-500');
                    emailError?.classList.remove('hidden');
                    isValid = false;
                } else {
                    emailInput.classList.remove('border-red-500');
                    emailError?.classList.add('hidden');
                }

                // Phone validation
                const phoneInput = document.getElementById('phone');
                const phoneError = phoneInput.nextElementSibling?.nextElementSibling;
                const phoneRegex = /^\d{10,12}$/;
                if (phoneInput.value.trim() && !phoneRegex.test(phoneInput.value.trim())) {
                    phoneInput.classList.add('border-red-500');
                    phoneError?.classList.remove('hidden');
                    isValid = false;
                } else {
                    phoneInput.classList.remove('border-red-500');
                    phoneError?.classList.add('hidden');
                }

                if (!isValid) {
                    e.preventDefault();
                    showToast('Please correct the errors in the form.', 'bg-red-500');
                }
            });

            // Real-time validation
            ['employee_id', 'username', 'first_name', 'last_name', 'email', 'department_id'].forEach(id => {
                const input = document.getElementById(id);
                input.addEventListener('input', () => {
                    const errorMessage = input.nextElementSibling?.nextElementSibling || input.nextElementSibling;
                    if (input.value.trim()) {
                        input.classList.remove('border-red-500');
                        errorMessage?.classList.add('hidden');
                    }
                });
            });

            document.getElementById('email').addEventListener('input', function() {
                const errorMessage = this.nextElementSibling?.nextElementSibling;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value.trim())) {
                    this.classList.remove('border-red-500');
                    errorMessage?.classList.add('hidden');
                }
            });

            document.getElementById('phone').addEventListener('input', function() {
                const errorMessage = this.nextElementSibling?.nextElementSibling;
                const phoneRegex = /^\d{10,12}$/;
                if (!this.value.trim() || phoneRegex.test(this.value.trim())) {
                    this.classList.remove('border-red-500');
                    errorMessage?.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>