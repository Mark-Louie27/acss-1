<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | ACSS</title>
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
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <header class="bg-white rounded-xl shadow-lg p-6 mb-8 flex flex-col sm:flex-row items-center justify-between slide-in-left">
            <div class="flex items-center mb-4 sm:mb-0">
                <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mr-4 overflow-hidden border-4 border-gold">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-4xl text-gray-dark"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-dark"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="text-sm font-medium text-gold"><?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-gray-dark"><?php echo htmlspecialchars($user['department_name'], ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($user['college_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
            <button id="editProfileBtn" class="btn-gold px-6 py-3 rounded-lg shadow-md hover:shadow-lg flex items-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gold focus:ring-opacity-50">
                <i class="fas fa-edit mr-2"></i> Edit Profile
            </button>
        </header>
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 fade-in">
                <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-gold"></i> Quick Stats
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Faculty</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($facultyCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Courses</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($coursesCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Pending Applicants</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($pendingApplicantsCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light">
                        <p class="text-sm text-gray-dark">Semester</p>
                        <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($currentSemester, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2 fade-in">
                <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-gold"></i> Personal Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-dark">First Name</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Middle Name</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['middle_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Last Name</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Suffix</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['suffix'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Email Address</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Phone Number</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-1 fade-in">
                <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-building mr-2 text-gold"></i> Department Information
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-dark">Employee ID</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['employee_id'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Department</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['department_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">College</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['college_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">College Code</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['college_id'] ? ($user['college_code'] ?? 'CCIT') : '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-dark">Role</p>
                        <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </section>
            <section class="bg-white rounded-xl shadow-lg p-6 lg:col-span-2 fade-in">
                <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center">
                    <i class="fas fa-clock mr-2 text-gold"></i> Recent Activity
                </h2>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-sign-in-alt text-gold"></i>
                        </span>
                        <div>
                            <p class="text-sm text-gray-dark">Last Login</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-edit text-gold"></i>
                        </span>
                        <div>
                            <p class="text-sm text-gray-dark">Profile Update</p>
                            <p class="text-sm font-medium text-gray-dark">Last updated on <?php echo date('F d, Y', strtotime($user['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
       
        <div id="editProfileModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl mx-4 transform modal-content scale-95">
                <div class="flex justify-between items-center p-6 border-b border-gray-light bg-gradient-to-r from-white to-gray-50 rounded-t-xl">
                    <h3 class="text-xl font-bold text-gray-dark">Edit Profile</h3>
                    <button id="closeModalBtn" class="text-gray-dark hover:text-gray-700 focus:outline-none bg-gray-light hover:bg-gray-200 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200" aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-dark mb-1">First Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50" aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">First name is required.</p>
                    </div>
                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-dark mb-1">Middle Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-dark mb-1">Last Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50" aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Last name is required.</p>
                    </div>
                    <div>
                        <label for="suffix" class="block text-sm font-medium text-gray-dark mb-1">Suffix</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-dark"></i>
                            </div>
                            <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($user['suffix'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-gray-dark mb-1">Email Address <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-dark"></i>
                            </div>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 bg-white shadow-sm input-focus focus:ring focus:ring-gold focus:ring-opacity-50" aria-required="true">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Valid email is required.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="phone" class="block text-sm font-medium text-gray-dark mb-1">Phone Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-dark"></i>
                            </div>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>" pattern="\d{10,12}" placeholder="10-12 digits" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 shadow-sm input-focus focus:ring focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-50 focus:ring-opacity-50">
                        </div>
                        <p class="text-red-500 text-xs mt-1 hidden error-message">Phone number must be between 10-12 digits</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="profile_picture" class="block text-sm font-medium text-gray-dark mb-1">Profile Picture</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-gray-50 file:text-gray-dark hover:file:bg-gray-100">
                            <p class="text-xs text-gray-dark mt-2">Accepted formats: JPEG, PNG, GIF (max 2MB)</p>
                        </div>
                    </div>
                    <div class="md:col-span-2 flex justify-end space-x-3 pt-4 border-t border-gray-light">
                        <button type="button" id="cancelModalBtn" class="bg-gray-light text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['flash'])): ?>
                showToast('<?php echo htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?>');
                <?php unset($_SESSION['flash']); ?>
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
                    modal.querySelectorAll('.error-message').forEach(msg => msg.classList.add('hidden'));
                    modal.querySelectorAll('input').forEach(input => input.classList.remove('border-red-500'));
                }, 200);
            }
            document.getElementById('editProfileBtn').addEventListener('click', openModal);
            document.getElementById('closeModalBtn').addEventListener('click', closeModal);
            document.getElementById('cancelModalBtn').addEventListener('click', closeModal);
            document.getElementById('editProfileModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('editProfileModal')) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !document.getElementById('editProfileModal').classList.contains('hidden')) closeModal();
            });
            const modalForm = document.querySelector('form');
            modalForm.addEventListener('submit', (e) => {
                let isValid = true;
                modalForm.querySelectorAll('input[required]').forEach(input => {
                    const errorMessage = input.nextElementSibling?.nextElementSibling;
                    if (!input.value.trim()) {
                        input.classList.add('border-red-500');
                        errorMessage?.classList.remove('hidden');
                        isValid = false;
                    } else {
                        input.classList.remove('border-red-500');
                        errorMessage?.classList.add('hidden');
                    }
                });
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
                if (!isValid) e.preventDefault();
            });
            modalForm.querySelectorAll('input[required]').forEach(input => {
                input.addEventListener('input', () => {
                    const errorMessage = input.nextElementSibling?.nextElementSibling;
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
