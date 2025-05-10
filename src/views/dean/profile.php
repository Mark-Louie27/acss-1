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
$collegeName = $controller->db->query("SELECT college_name FROM colleges WHERE college_id = $collegeId")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Profile | ACSS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/output.css">
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
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <header class="mb-8">
            <h2 class="text-4xl font-bold text-gray-dark slide-in-left">Dean Profile</h2>
            <p class="text-gray-dark mt-2">Manage your personal and professional information</p>
        </header>

        <!-- Profile Overview -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8 fade-in" role="region" aria-label="Profile Overview">
            <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                <!-- Profile Picture -->
                <div class="flex-shrink-0">
                    <?php if ($user['profile_picture']): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-40 h-40 rounded-full object-cover border-4 border-gold shadow-md" aria-label="Current profile picture">
                    <?php else: ?>
                        <div class="w-40 h-40 rounded-full bg-gray-light flex items-center justify-center text-gray-dark text-3xl font-bold border-4 border-gold shadow-md" aria-label="Profile picture placeholder">
                            <?php echo htmlspecialchars(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- User Info -->
                <div class="text-center md:text-left">
                    <h3 class="text-2xl font-semibold text-gray-dark">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ($user['suffix'] ? ' ' . $user['suffix'] : '')); ?>
                    </h3>
                    <p class="text-gray-dark font-medium">Dean, <?php echo htmlspecialchars($collegeName ?: 'N/A'); ?></p>
                    <p class="text-gray-dark mt-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="text-gray-dark"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                    <p class="text-gray-dark mt-1">Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
                </div>
            </div>
        </div>

        <!-- Update Profile Form -->
        <div class="bg-white rounded-xl shadow-lg p-8 fade-in" role="region" aria-label="Update Profile Form">
            <h3 class="text-xl font-semibold text-gray-dark mb-6">Update Profile Information</h3>
            <form action="/dean/profile" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6" id="profileForm">
                <!-- Employee ID -->
                <div>
                    <label for="employee_id" class="block text-sm font-medium text-gray-dark">Employee ID <span class="text-red-500">*</span></label>
                    <input type="text" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-dark">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-dark">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-dark">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                </div>

                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-dark">First Name <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                </div>

                <!-- Middle Name -->
                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-dark">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-dark">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                </div>

                <!-- Suffix -->
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-dark">Suffix</label>
                    <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($user['suffix'] ?? ''); ?>"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                </div>

                <!-- Department -->
                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-dark">Department <span class="text-red-500">*</span></label>
                    <select id="department_id" name="department_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50"
                        aria-required="true">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>" <?php echo $user['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Profile Picture -->
                <div>
                    <label for="profile_picture" class="block text-sm font-medium text-gray-dark">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                        class="mt-1 block w-full text-sm text-gray-dark file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gold file:text-white file:btn-gold">
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn-gold px-6 py-2 rounded-md shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-gold focus:ring-opacity-50 transition duration-200">
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Toast Notifications and Form Validation -->
    <script>
        // Toast Notifications
        <?php if ($success): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('<?php echo $success; ?>', 'bg-green-500');
            });
        <?php endif; ?>
        <?php if ($error): ?>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('<?php echo $error; ?>', 'bg-red-500');
            });
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

        // Form Validation
        document.getElementById('profileForm').addEventListener('submit', (e) => {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showToast('Please enter a valid email address', 'bg-red-500');
                return;
            }

            const phone = document.getElementById('phone').value;
            const phoneRegex = /^\d{10,12}$/;
            if (phone && !phoneRegex.test(phone)) {
                e.preventDefault();
                showToast('Please enter a valid phone number (10-12 digits)', 'bg-red-500');
                return;
            }

            const employeeId = document.getElementById('employee_id').value;
            if (!employeeId.trim()) {
                e.preventDefault();
                showToast('Employee ID is required', 'bg-red-500');
                return;
            }

            const username = document.getElementById('username').value;
            if (!username.trim()) {
                e.preventDefault();
                showToast('Username is required', 'bg-red-500');
                return;
            }
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>