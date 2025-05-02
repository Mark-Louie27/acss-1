<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../services/AuthService.php';

// Initialize database connection and services
$db = (new Database())->connect();
$userModel = new UserModel($db);
$authService = new AuthService($db);

// Initialize variables
$error = '';
$success = '';
$roles = [];
$colleges = [];
$departments = [];

// Fetch roles, colleges, and departments for dropdowns
try {
    // Get all roles
    $roles = $userModel->getRoles();

    // Get all colleges
    $colleges = $userModel->getColleges();

    // Get departments (we'll populate based on college selection via AJAX)
    $departments = [];
} catch (Exception $e) {
    $error = "Error loading registration data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Basic validation
        $requiredFields = [
            'employee_id',
            'username',
            'password',
            'confirm_password',
            'email',
            'first_name',
            'last_name',
            'role_id',
            'college_id',
            'department_id'
        ];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("All fields are required.");
            }
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

        // Prepare user data
        $userData = [
            'employee_id' => trim($_POST['employee_id']),
            'username' => trim($_POST['username']),
            'password' => $_POST['password'],
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone'] ?? ''),
            'first_name' => trim($_POST['first_name']),
            'middle_name' => trim($_POST['middle_name'] ?? ''),
            'last_name' => trim($_POST['last_name']),
            'suffix' => trim($_POST['suffix'] ?? ''),
            'role_id' => (int)$_POST['role_id'],
            'college_id' => (int)$_POST['college_id'],
            'department_id' => (int)$_POST['department_id'],
            'is_active' => 1
        ];

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExt = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $userData['employee_id'] . '_' . time() . '.' . $fileExt;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                $userData['profile_picture'] = '/uploads/profiles/' . $fileName;
            }
        }

        // Register the user
        if ($authService->register($userData)) {
            $success = "Registration successful! You can now login.";
            header('Location: /login?success=' . urlencode($success));
            exit;
        } else {
            throw new Exception("Registration failed. Please try again.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="/css/output.css">
    <script src="https://cdn.tailwindcss.com"></script>

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
                            /* Primary gold */
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
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 py-6 px-8 border-b-4 border-gold-500">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-user-plus mr-3 text-gold-400"></i>
                            User Registration
                        </h2>
                        <div class="h-12 w-12 rounded-full bg-gray-700 border-2 border-gold-400 flex items-center justify-center">
                            <i class="fas fa-university text-gold-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <div class="px-8 pt-6">
                    <?php if (isset($error)): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" class="px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div class="relative">
                                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Employee ID <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-id-card text-gray-400"></i>
                                    </div>
                                    <input type="text" id="employee_id" name="employee_id" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" id="username" name="username" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="password" name="password" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" id="confirm_password" name="confirm_password" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md">
                                </div>
                                <p id="password-error" class="mt-1 text-sm text-red-600 hidden">Passwords do not match</p>
                            </div>

                            <div class="relative">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" id="email" name="email" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <div class="relative">
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-file-signature text-gray-400"></i>
                                    </div>
                                    <input type="text" id="first_name" name="first_name" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Middle Name
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-file-signature text-gray-400"></i>
                                    </div>
                                    <input type="text" id="middle_name" name="middle_name"
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-file-signature text-gray-400"></i>
                                    </div>
                                    <input type="text" id="last_name" name="last_name" required
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">
                                    Suffix
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-file-signature text-gray-400"></i>
                                    </div>
                                    <input type="text" id="suffix" name="suffix"
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="relative">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone Number
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone text-gray-400"></i>
                                    </div>
                                    <input type="tel" id="phone" name="phone"
                                        class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md"
                                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role, College, Department Row -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="relative">
                            <label for="role_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user-tag text-gray-400"></i>
                                </div>
                                <select id="role_id" name="role_id" required
                                    class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md appearance-none">
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>"
                                            <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <label for="college_id" class="block text-sm font-medium text-gray-700 mb-1">
                                College <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-university text-gray-400"></i>
                                </div>
                                <select id="college_id" name="college_id" required
                                    class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md appearance-none">
                                    <option value="">Select College</option>
                                    <?php foreach ($colleges as $college): ?>
                                        <option value="<?= $college['college_id'] ?>"
                                            <?= (isset($_POST['college_id']) && $_POST['college_id'] == $college['college_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($college['college_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Department <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <select id="department_id" name="department_id" required
                                    class="focus:ring-gold-500 focus:border-gold-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md appearance-none">
                                    <option value="">Select Department</option>
                                    <?php
                                    // If college is already selected, show its departments
                                    if (isset($_POST['college_id']) && !empty($_POST['college_id'])) {
                                        $selectedCollegeId = (int)$_POST['college_id'];
                                        $departments = $userModel->getDepartmentsByCollege($selectedCollegeId);

                                        foreach ($departments as $dept) {
                                            echo '<option value="' . $dept['department_id'] . '" ' .
                                                ((isset($_POST['department_id']) && $_POST['department_id'] == $dept['department_id']) ? 'selected' : '') . '>' .
                                                htmlspecialchars($dept['department_name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Picture -->
                    <div class="mt-8">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1">
                            Profile Picture
                        </label>
                        <div class="mt-1 flex items-center space-x-5">
                            <div class="flex-shrink-0 h-14 w-14 bg-gray-200 rounded-full flex items-center justify-center border-2 border-gray-300">
                                <i class="fas fa-user text-gray-400 text-2xl"></i>
                            </div>
                            <label for="profile_picture" class="cursor-pointer">
                                <div class="relative">
                                    <div class="py-2 px-4 border border-gray-300 rounded-md bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-500 inline-flex items-center">
                                        <i class="fas fa-upload mr-2 text-gray-400"></i>
                                        <span>Upload Image</span>
                                    </div>
                                    <input id="profile_picture" name="profile_picture" type="file" class="sr-only" accept="image/*">
                                </div>
                            </label>
                            <p class="text-xs text-gray-500" id="file-name">No file selected</p>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-10 flex justify-center space-x-4">
                        <button type="submit" class="bg-gradient-to-r from-gray-800 to-gray-700 hover:from-gray-700 hover:to-gray-600 text-white px-8 py-3 rounded-md font-medium shadow-md border-b-4 border-gold-500 hover:border-gold-400 transition duration-300 flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Register
                        </button>
                        <a href="/login" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-8 py-3 rounded-md font-medium shadow-md transition duration-300 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center text-gray-500 text-sm">
                <p>&copy; <?= date('Y') ?> All rights reserved</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load departments when college is selected
            $('#college_id').change(function() {
                const collegeId = $(this).val();
                if (collegeId) {
                    $.ajax({
                        url: '/api/departments?college_id=' + collegeId,
                        method: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            const deptSelect = $('#department_id');
                            deptSelect.empty();
                            deptSelect.append('<option value="">Select Department</option>');

                            data.departments.forEach(function(dept) {
                                deptSelect.append(`<option value="${dept.department_id}">${dept.department_name}</option>`);
                            });
                        },
                        error: function() {
                            console.error('Error loading departments');
                        }
                    });
                } else {
                    $('#department_id').empty().append('<option value="">Select Department</option>');
                }
            });

            // Password match validation
            $('#confirm_password, #password').on('keyup', function() {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                const errorElement = $('#password-error');

                if (confirmPassword && password !== confirmPassword) {
                    $('#confirm_password')[0].setCustomValidity("Passwords do not match");
                    errorElement.removeClass('hidden');
                } else {
                    $('#confirm_password')[0].setCustomValidity('');
                    errorElement.addClass('hidden');
                }
            });

            // File input display
            $('#profile_picture').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $('#file-name').text(fileName ? fileName : 'No file selected');
            });
        });
    </script>
</body>

</html>