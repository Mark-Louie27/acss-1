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
    $roles = $userModel->getRoles();
    $colleges = $userModel->getColleges();
    $departments = [];
} catch (Exception $e) {
    $error = "Error loading registration data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
                throw new Exception("All required fields must be filled.");
            }
        }

        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match.");
        }

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

        // Add academic rank, employment type, and classification if Faculty role (role_id = 6)
        if ($userData['role_id'] == 6) {
            $requiredFields[] = 'academic_rank';
            $requiredFields[] = 'employment_type';
            if (empty($_POST['academic_rank'])) {
                throw new Exception("Academic rank is required for Faculty.");
            }
            if (empty($_POST['employment_type'])) {
                throw new Exception("Employment type is required for Faculty.");
            }
            $userData['academic_rank'] = trim($_POST['academic_rank']);
            $userData['employment_type'] = trim($_POST['employment_type']);
            $userData['classification'] = trim($_POST['classification'] ?? null); // Optional, defaults to NULL
        }

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
    <title>Register | PRMSU Scheduling System</title>
    <meta name="description" content="Register for the President Ramon Magsaysay State University Scheduling System.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('/assets/logo/main_logo/campus.jpg');
            background-size: cover;
            background-position: center;
            z-index: 1;
        }

        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 font-poppins">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Section (Background with University Image, Logo and Text) -->
        <div class="w-full md:w-1/2 text-white flex items-center justify-center p-6 md:p-12 relative overflow-hidden">
            <div class="bg-image"></div>
            <div class="bg-overlay"></div>

            <div class="text-center z-10 flex flex-col items-center">
                <div class="mb-6">
                    <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" class="w-24 h-24 md:w-32 md:h-32 mx-auto">
                </div>
                <h1 class="text-3xl md:text-4xl font-bold mb-4">President Ramon Magsaysay State University</h1>
                <h2 class="text-xl md:text-2xl font-semibold mb-4">Scheduling System</h2>
                <p class="text-base md:text-lg mb-6">Streamlining class scheduling for better academic planning and resource management.</p>
                <p class="text-sm md:text-md italic">"Quality Education for Service"</p>
            </div>
        </div>

        <!-- Right Section (Registration Form) -->
        <div class="w-full md:w-1/2 bg-white flex items-center justify-center p-6 md:p-12 overflow-y-auto">
            <div class="w-full max-w-md">
                <div class="text-center mb-6">
                    <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" class="mx-auto w-24 md:w-18 rounded-full border-4 border-white shadow-lg">
                    <h1 class="text-xl md:text-2xl font-bold text-yellow-600 mb-2">Create Your Account</h1>
                    <p class="text-sm md:text-base text-gray-600">Register to access the scheduling system</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 4a8 8 0 100 16 8 8 0 000-16z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="border-b border-gray-200 pb-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Personal Information</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="first_name" class="block text-xs md:text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="first_name" name="first_name" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" placeholder="Enter your first name">
                                    </div>
                                </div>
                                <div>
                                    <label for="middle_name" class="block text-xs md:text-sm font-medium text-gray-700">Middle Name</label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="middle_name" name="middle_name" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>" placeholder="Enter your middle name">
                                    </div>
                                </div>
                                <div>
                                    <label for="last_name" class="block text-xs md:text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="last_name" name="last_name" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" placeholder="Enter your last name">
                                    </div>
                                </div>
                                <div>
                                    <label for="suffix" class="block text-xs md:text-sm font-medium text-gray-700">Suffix</label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="suffix" name="suffix" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>" placeholder="e.g., Jr., Sr.">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-xs md:text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                        </div>
                                        <input type="email" id="email" name="email" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Enter your email">
                                    </div>
                                </div>
                                <div>
                                    <label for="phone" class="block text-xs md:text-sm font-medium text-gray-700">(Optional) Phone Number</label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <input type="tel" id="phone" name="phone" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="Enter your phone number">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="employee_id" class="block text-xs md:text-sm font-medium text-gray-700">Employee ID <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                        </div>
                                        <input type="text" id="employee_id" name="employee_id" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>" placeholder="Enter your employee ID">
                                    </div>
                                </div>
                                <div>
                                    <label for="username" class="block text-xs md:text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="username" name="username" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Enter your username">
                                    </div>
                                </div>
                                <div>
                                    <label for="password" class="block text-xs md:text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input type="password" id="password" name="password" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" placeholder="Enter your password">
                                    </div>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-xs md:text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input type="password" id="confirm_password" name="confirm_password" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" placeholder="Confirm your password">
                                    </div>
                                    <p id="password-error" class="mt-1 text-xs md:text-sm text-red-600 hidden">Passwords do not match</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Academic Information</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="role_id" class="block text-xs md:text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a2 2 0 00-2-2h-3m-2 4h-5a2 2 0 01-2-2v-3m7-7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <select id="role_id" name="role_id" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none" onchange="toggleFacultyFields()">
                                            <option value="">Select Role</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= $role['role_id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role['role_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="college_id" class="block text-xs md:text-sm font-medium text-gray-700">College <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a2 2 0 012-2h2a2 2 0 012 2v5m-4-6h.01" />
                                            </svg>
                                        </div>
                                        <select id="college_id" name="college_id" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none">
                                            <option value="">Select College</option>
                                            <?php foreach ($colleges as $college): ?>
                                                <option value="<?= $college['college_id'] ?>" <?= (isset($_POST['college_id']) && $_POST['college_id'] == $college['college_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($college['college_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="department_id" class="block text-xs md:text-sm font-medium text-gray-700">Department <span class="text-red-500">*</span></label>
                                    <div class="mt-1 relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a2 2 0 012-2h2a2 2 0 012 2v5m-4-6h.01" />
                                            </svg>
                                        </div>
                                        <select id="department_id" name="department_id" required class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none">
                                            <option value="">Select Department</option>
                                            <?php
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
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="faculty-fields" class="space-y-4 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="academic_rank" class="block text-xs md:text-sm font-medium text-gray-700">Academic Rank <span class="text-red-500">*</span></label>
                                        <div class="mt-1 relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                </svg>
                                            </div>
                                            <select id="academic_rank" name="academic_rank" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none">
                                                <option value="">Select Academic Rank</option>
                                                <option value="Instructor" <?= (isset($_POST['academic_rank']) && $_POST['academic_rank'] == 'Instructor') ? 'selected' : '' ?>>Instructor</option>
                                                <option value="Assistant Professor" <?= (isset($_POST['academic_rank']) && $_POST['academic_rank'] == 'Assistant Professor') ? 'selected' : '' ?>>Assistant Professor</option>
                                                <option value="Associate Professor" <?= (isset($_POST['academic_rank']) && $_POST['academic_rank'] == 'Associate Professor') ? 'selected' : '' ?>>Associate Professor</option>
                                                <option value="Professor" <?= (isset($_POST['academic_rank']) && $_POST['academic_rank'] == 'Professor') ? 'selected' : '' ?>>Professor</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="employment_type" class="block text-xs md:text-sm font-medium text-gray-700">Employment Type <span class="text-red-500">*</span></label>
                                        <div class="mt-1 relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <select id="employment_type" name="employment_type" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none">
                                                <option value="">Select Employment Type</option>
                                                <option value="Regular" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Regular') ? 'selected' : '' ?>>Regular</option>
                                                <option value="Contractual" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Contractual') ? 'selected' : '' ?>>Contractual</option>
                                                <option value="Part-time" <?= (isset($_POST['employment_type']) && $_POST['employment_type'] == 'Part-time') ? 'selected' : '' ?>>Part-time</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="classification" class="block text-xs md:text-sm font-medium text-gray-700">Classification</label>
                                        <div class="mt-1 relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                </svg>
                                            </div>
                                            <select id="classification" name="classification" class="block w-full pl-9 md:pl-10 pr-3 py-2 md:py-2 border border-gray-300 rounded-md shadow-sm text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 appearance-none">
                                                <option value="">Select Classification</option>
                                                <option value="TL" <?= (isset($_POST['classification']) && $_POST['classification'] == 'TL') ? 'selected' : '' ?>>TL</option>
                                                <option value="VSL" <?= (isset($_POST['classification']) && $_POST['classification'] == 'VSL') ? 'selected' : '' ?>>VSL</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="h-4 md:h-5 w-4 md:w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-row md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4 mt-6">
                        <button type="submit" class="bg-yellow-600 w-full text-white py-2 px-6 rounded-md hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition duration-150 ease-in-out text-sm md:text-base">
                            Register
                        </button>
                    </div>
                    <div class="text-center mt-4">
                        <p class="text-xs md:text-sm text-gray-600">Already have an account? <a href="/login" class="text-yellow-600 hover:text-yellow-500">log in account</a></p>
                    </div>
                </form>

                <div class="text-center mt-4 text-xs md:text-sm text-gray-500">
                    © 2025 President Ramon Magsaysay State University. All rights reserved.
                </div>
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

            // Toggle faculty fields based on role selection
            function toggleFacultyFields() {
                const roleId = $('#role_id').val();
                const facultyFields = $('#faculty-fields');
                if (roleId == 6) {
                    facultyFields.removeClass('hidden');
                } else {
                    facultyFields.addClass('hidden');
                }
            }

            // Initial check on page load
            toggleFacultyFields();

            // Re-check when role changes
            $('#role_id').change(toggleFacultyFields);
        });
    </script>
</body>

</html>