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
            animation: fadeIn 0.5s ease-in-out;
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

        .slide-in-left {
            animation: slideInLeft 0.5s ease-in-out;
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
            transition: all 0.3s ease-in-out;
        }

        .modal {
            transition: opacity 0.3s ease-in-out;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            transition: transform 0.3s ease-in-out;
            transform-origin: center;
        }

        .input-focus:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
            outline: none;
        }

        .btn-gold {
            background-color: var(--gold);
            color: var(--white);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-gold:hover {
            background-color: #b8972e;
            transform: translateY(-1px);
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

        .profile-card {
            background: linear-gradient(135deg, var(--white) 0%, #fafafa 100%);
            border-radius: 12px;
            overflow: hidden;
        }

        @media (max-width: 640px) {
            .grid-cols-layout {
                grid-template-columns: 1fr;
            }

            .modal-content {
                max-width: 90vw;
            }
        }

        @media (min-width: 1024px) {
            .grid-cols-layout {
                grid-template-columns: 1fr 2fr 1fr;
            }
        }
    </style>
</head>

<body class="bg-gray-light font-sans antialiased">

    <body class="bg-gray-100 font-sans antialiased">
        <div id="toast-container" class="fixed top-5 right-5 z-50"></div>
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <header class="profile-card shadow-lg p-6 mb-8 flex flex-col sm:flex-row items-center justify-between slide-in-left">
                <div class="flex items-center space-x-6 mb-4 sm:mb-0">
                    <div class="w-28 h-28 bg-gray-200 rounded-full overflow-hidden border-4 border-gold flex items-center justify-center relative">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-5xl text-gray-dark"></i>
                        <?php endif; ?>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-3xl font-bold text-gray-dark"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="text-sm font-medium text-gold"><?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-sm text-gray-dark"><?php echo htmlspecialchars($user['department_name'], ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($user['college_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <button id="editProfileBtn" class="btn-gold px-6 py-3 rounded-lg shadow-md flex items-center space-x-2 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gold focus:ring-opacity-50">
                    <i class="fas fa-edit"></i>
                    <span>Edit Profile</span>
                </button>
            </header>

            <main class="grid grid-cols-layout gap-6">
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                        <i class="fas fa-chart-bar text-gold"></i>
                        <span>Quick Stats</span>
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light hover:bg-gray-100 transition-colors">
                            <p class="text-sm text-gray-dark">Faculty</p>
                            <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($facultyCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light hover:bg-gray-100 transition-colors">
                            <p class="text-sm text-gray-dark">Courses</p>
                            <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($coursesCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light hover:bg-gray-100 transition-colors">
                            <p class="text-sm text-gray-dark">Pending Applicants</p>
                            <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($pendingApplicantsCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-light hover:bg-gray-100 transition-colors">
                            <p class="text-sm text-gray-dark">Semester</p>
                            <p class="text-xl font-bold text-gold"><?php echo htmlspecialchars($currentSemester, ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </section>
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                        <i class="fas fa-user text-gold"></i>
                        <span>Personal Information</span>
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
                        <div class="sm:col-span-2">
                            <p class="text-sm text-gray-dark">Email Address</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-sm text-gray-dark">Phone Number</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-sm text-gray-dark">Classification</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['classification'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-sm text-gray-dark">Subject Specialization</p>
                            <p class="text-sm font-medium text-gray-dark"><?php echo htmlspecialchars($user['subject_specialization'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                </section>
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                        <i class="fas fa-building text-gold"></i>
                        <span>Department Information</span>
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
                    <h2 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                        <i class="fas fa-clock text-gold"></i>
                        <span>Recent Activity</span>
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
                    <div class="bg-[#E69F54] text-white p-6 rounded-t-xl flex flex-col sm:flex-row items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-3xl text-gray-dark bg-gray-200 w-full h-full flex items-center justify-center"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p class="text-sm font-medium text-gray-200"><?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($user['department_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <button id="closeModalBtn" class="text-white hover:text-gray-200 focus:outline-none bg-white bg-opacity-10 hover:bg-opacity-20 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="p-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <h4 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                                    <i class="fas fa-user text-gold"></i>
                                    <span>Personal Information</span>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="first_name_modal" class="block text-sm font-medium text-gray-dark mb-1">First Name</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="first_name_modal" name="first_name" required value="<?php echo htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 bg-white shadow-sm input-focus" aria-required="true">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="last_name_modal" class="block text-sm font-medium text-gray-dark mb-1">Last Name</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="last_name_modal" name="last_name" required value="<?php echo htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 bg-white shadow-sm input-focus" aria-required="true">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="email_modal" class="block text-sm font-medium text-gray-dark mb-1">Email Address</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400"></i>
                                            </div>
                                            <input type="email" id="email_modal" name="email" required value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 bg-white shadow-sm input-focus" aria-required="true">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="phone_modal" class="block text-sm font-medium text-gray-dark mb-1">Phone Number</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-phone text-gray-400"></i>
                                            </div>
                                            <input type="text" id="phone_modal" name="phone" value="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-300 bg-white shadow-sm input-focus">
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="profile_picture" class="block text-sm font-medium text-gray-dark mb-1">Profile Picture</label>
                                        <div class="file-input-wrapper relative">
                                            <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-gray-50 file:text-gray-dark hover:file:bg-gray-100">
                                            <p class="text-xs text-gray-dark mt-2">Accepted formats: JPEG, PNG, GIF (max 2MB)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 mt-6">
                                <h4 class="text-lg font-semibold text-gray-dark mb-4 flex items-center space-x-2">
                                    <i class="fas fa-book text-gold"></i>
                                    <span>Academic Information</span>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <div class="md:col-span-2">
                                            <label for="classification" class="block text-sm font-medium text-gray-dark mb-1">Classification</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-graduation-cap text-gray-dark"></i>
                                                </div>
                                                <select id="classification" name="classification" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus">
                                                    <option value="" <?php echo empty($user['classification']) ? 'selected' : ''; ?>>Select Classification</option>
                                                    <option value="Beginner" <?php echo $user['classification'] === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                                    <option value="Intermediate" <?php echo $user['classification'] === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                    <option value="Expert" <?php echo $user['classification'] === 'Expert' ? 'selected' : ''; ?>>Expert</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="subject_specialization" class="block text-sm font-medium text-gray-dark mb-1">Subject Specialization</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-book text-gray-dark"></i>
                                                </div>
                                                <select id="subject_specialization" name="subject_specialization" class="pl-10 pr-4 py-3 w-full rounded-lg border-gray-light bg-white shadow-sm input-focus">
                                                    <option value="">Select Subject Specialization</option>
                                                    <?php
                                                    $stmt = $this->db->prepare("SELECT course_id, course_name FROM courses WHERE is_active = 1");
                                                    $stmt->execute();
                                                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($courses as $course) {
                                                        $selected = ($user['subject_specialization'] == $course['course_id']) ? 'selected' : '';
                                                        echo "<option value=\"{$course['course_id']}\" $selected>{$course['course_name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <label for="academic_rank" class="block text-sm font-medium text-gray-dark mb-1">Academic Rank</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-graduation-cap text-gray-400"></i>
                                            </div>
                                            <select id="academic_rank" name="academic_rank" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                                <option value="">Select Academic Rank</option>
                                                <option value="Professor" <?php echo $user['academic_rank'] === 'Professor' ? 'selected' : ''; ?>>Professor</option>
                                                <option value="Associate Professor" <?php echo $user['academic_rank'] === 'Associate Professor' ? 'selected' : ''; ?>>Associate Professor</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="employment_type" class="block text-sm font-medium text-gray-dark mb-1">Employment Type</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-briefcase text-gray-400"></i>
                                            </div>
                                            <select id="employment_type" name="employment_type" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                                <option value="">Select Employment Type</option>
                                                <option value="Full-time" <?php echo $user['employment_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                                <option value="Part-time" <?php echo $user['employment_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="classification" class="block text-sm font-medium text-gray-dark mb-1">Classification</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-tag text-gray-400"></i>
                                            </div>
                                            <select id="classification" name="classification" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                                <option value="">Select Classification</option>
                                                <option value="VSL" <?php echo $user['classification'] === 'VSL' ? 'selected' : ''; ?>>VSL</option>
                                                <option value="VPL" <?php echo $user['classification'] === 'VPL' ? 'selected' : ''; ?>>VPL</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-2 flex justify-end space-x-3 pt-4 border-t border-gray-200 mt-6">
                            <button type="button" id="cancelModalBtn" class="bg-gray-200 text-gray-dark px-5 py-3 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">Cancel</button>
                            <button type="submit" class="btn-gold px-5 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Save Academic Changes</button>
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
                    toast.className = `toast ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg flex items-center`;
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
                    setTimeout(() => modalContent.classList.add('scale-100'), 50);
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    const modal = document.getElementById('editProfileModal');
                    const modalContent = modal.querySelector('.modal-content');
                    modalContent.classList.remove('scale-100');
                    setTimeout(() => {
                        modal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                        modal.querySelectorAll('.error-message').forEach(msg => msg.classList.add('hidden'));
                        modal.querySelectorAll('input').forEach(input => input.classList.remove('border-red-500'));
                        document.getElementById('profilePreview').classList.add('hidden');
                        document.getElementById('profilePreview').querySelector('img').src = '';
                    }, 300);
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

                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    if (newPassword || confirmPassword) {
                        if (newPassword !== confirmPassword) {
                            const errorMsg = document.createElement('p');
                            errorMsg.className = 'text-red-500 text-xs mt-1';
                            errorMsg.textContent = 'New passwords do not match.';
                            document.getElementById('confirm_password').parentElement.appendChild(errorMsg);
                            isValid = false;
                        } else if (newPassword.length < 8) {
                            const errorMsg = document.createElement('p');
                            errorMsg.className = 'text-red-500 text-xs mt-1';
                            errorMsg.textContent = 'Password must be at least 8 characters long.';
                            document.getElementById('new_password').parentElement.appendChild(errorMsg);
                            isValid = false;
                        }
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
                    const emailRegex = /^[^\s@]+@[^\s@]+.[^\s@]+$/;
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
                const profileInput = document.getElementById('profile_picture');
                const preview = document.getElementById('profilePreview');
                const previewImg = preview.querySelector('img');
                profileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!validTypes.includes(file.type)) {
                            showToast('Only JPEG, PNG, or GIF files are allowed.', 'bg-red-500');
                            this.value = '';
                            return;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            showToast('File size must be less than 2MB.', 'bg-red-500');
                            this.value = '';
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImg.src = e.target.result;
                            preview.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
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