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
            --orange: #E69F54;
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

        .btn-orange {
            background-color: var(--orange);
            color: var(--white);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-orange:hover {
            background-color: #d48a43;
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

        .specialization-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .specialization-card:hover {
            border-color: var(--orange);
            box-shadow: 0 4px 12px rgba(230, 159, 84, 0.15);
        }

        .quick-stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .quick-stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .specialization-level {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
        }

        .level-beginner {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .level-intermediate {
            background-color: #fef3c7;
            color: #d97706;
        }

        .level-expert {
            background-color: #dcfce7;
            color: #16a34a;
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
                grid-template-columns: 1fr 280px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Profile Header -->
        <header class="bg-gray-800 text-white p-6 mb-8 rounded-xl shadow-lg slide-in-left">
            <div class="flex flex-col sm:flex-row items-center justify-between">
                <div class="flex items-center space-x-6 mb-4 sm:mb-0">
                    <div class="w-20 h-20 bg-white rounded-full overflow-hidden border-4 border-white flex items-center justify-center relative">
                        <?php if (!empty($faculty['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($faculty['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-3xl text-gray-600"></i>
                        <?php endif; ?>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-2 border-white"></div>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($faculty['title'] . ' ' . $faculty['first_name'] . ' ' . $faculty['middle_name'] . ' ' . $faculty['last_name'] . ' ' . $faculty['suffix'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="text-sm font-medium text-orange-100"><?php echo htmlspecialchars($faculty['department_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-orange-100 flex items-center mt-1">
                            <span class="inline-block w-2 h-2 bg-orange-200 rounded-full mr-2"></span>
                            Faculty
                        </p>
                    </div>
                </div>
                <button id="editProfileBtn" class="bg-yellow-600 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg shadow-md flex items-center space-x-2 transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                    <i class="fas fa-edit text-sm"></i>
                    <span class="text-sm font-medium">Edit Profile</span>
                </button>
            </div>
        </header>

        <main class="grid grid-cols-layout gap-6">
            <!-- Main Content -->
            <div class="space-y-6">
                <!-- Personal Information -->
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user text-orange-500 mr-2"></i>
                        Personal Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-600">First Name</label>
                            <p class="mt-1 text-gray-800 font-medium"><?php echo htmlspecialchars($faculty['first_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Middle Name</label>
                            <p class="mt-1 text-gray-800 font-medium"><?php echo htmlspecialchars($faculty['middle_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Last Name</label>
                            <p class="mt-1 text-gray-800 font-medium"><?php echo htmlspecialchars($faculty['last_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Suffix</label>
                            <p class="mt-1 text-gray-800 font-medium"><?php echo htmlspecialchars($faculty['suffix'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Email Address</label>
                            <p class="mt-1 text-gray-800 font-medium flex items-center">
                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($faculty['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Phone Number</label>
                            <p class="mt-1 text-gray-800 font-medium flex items-center">
                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($faculty['phone'] ?? 'Not provided', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Academic Information -->
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-graduation-cap text-orange-500 mr-2"></i>
                        Academic Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Academic Rank</label>
                            <p class="mt-1 text-gray-800 font-medium flex items-center">
                                <i class="fas fa-award text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($faculty['academic_rank'] ?? 'Instructor', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Employment Type</label>
                            <p class="mt-1 text-gray-800 font-medium flex items-center">
                                <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($faculty['employment_type'] ?? 'Part-time', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-gray-600">Classification</label>
                            <p class="mt-1 text-gray-800 font-medium flex items-center">
                                <i class="fas fa-tag text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($faculty['classification'] ?? 'TL', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Specializations -->
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <i class="fas fa-star text-orange-500 mr-2"></i>
                            Your Subject Specializations
                        </h2>
                        <button id="addSpecializationBtn2" class="btn-orange px-4 py-2 rounded-lg shadow-md flex items-center space-x-2 text-sm">
                            <i class="fas fa-plus"></i>
                            <span>Add Subject Specialization</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php if (!empty($specializations)): ?>
                            <?php foreach ($specializations as $specialization): ?>
                                <div class="specialization-card p-4 rounded-lg">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <span class="specialization-level level-<?php echo strtolower($specialization['expertise_level']); ?>">
                                                <?php echo htmlspecialchars($specialization['expertise_level'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </div>
                                        <div class="flex space-x-1">
                                            <button class="text-gray-400 hover:text-blue-500 transition-colors" title="Edit">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this specialization?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="delete_specialization" value="1">
                                                <input type="hidden" name="specialization_id" value="<?php echo htmlspecialchars($specialization['specialization_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Remove">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($specialization['course_code'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($specialization['course_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="md:col-span-2 lg:col-span-3 text-center py-8">
                                <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 mb-4">No Subject specializations added yet</p>
                                <button id="addFirstSpecialization" class="btn-orange px-6 py-3 rounded-lg shadow-md">
                                    Add Your First Subject Specialization
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Stats -->
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-line text-orange-500 mr-2"></i>
                        Quick Stats
                    </h2>
                    <div class="space-y-4">
                        <div class="quick-stats-card p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-graduation-cap text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Courses Assigned</p>
                                        <p class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($courseCount ?? 0, ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-stats-card p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-clock text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Teaching Hours</p>
                                        <p class="text-2xl font-bold text-green-600"><?php echo htmlspecialchars(number_format($teachingHours, 1), ENT_QUOTES, 'UTF-8'); ?> <span class="text-xs font-normal text-gray-500">hrs/wk</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-stats-card p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-star text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Specializations</p>
                                        <p class="text-2xl font-bold text-purple-600"><?php echo htmlspecialchars(count($specializations), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Quick Actions -->
                <section class="bg-white rounded-xl shadow-lg p-6 fade-in">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-bolt text-orange-500 mr-2"></i>
                        Quick Actions
                    </h2>
                    <div class="space-y-3">
                        <button class="w-full flex items-center space-x-3 p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-blue-600 text-sm"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">View Schedule</span>
                        </button>
                        <button id="addSpecializationBtn" class="w-full flex items-center space-x-3 p-3 text-left hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-plus text-purple-600 text-sm"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">Add Subject Specialization</span>
                        </button>
                    </div>
                </section>
            </div>
        </main>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-4xl mx-4 transform modal-content scale-95 max-h-[90vh] overflow-y-auto">
                <div class="bg-yellow-500 text-white p-6 rounded-t-xl flex flex-col sm:flex-row items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-white">
                            <?php if (!empty($faculty['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($faculty['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-3xl text-gray-600 bg-gray-200 w-full h-full flex items-center justify-center"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="text-sm font-medium text-orange-100">Faculty | <?php echo htmlspecialchars($faculty['department_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </div>
                    <button id="closeModalBtn" class="text-white hover:text-orange-200 focus:outline-none bg-white bg-opacity-10 hover:bg-opacity-20 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200" aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data" action="/faculty/profile" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center space-x-2">
                                <i class="fas fa-user text-orange-500"></i>
                                <span>Personal Information</span>
                            </h4>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="first_name_modal" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="first_name_modal" name="first_name" required value="<?php echo htmlspecialchars($faculty['first_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="middle_name_modal" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="middle_name_modal" name="middle_name" value="<?php echo htmlspecialchars($faculty['middle_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="last_name_modal" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="last_name_modal" name="last_name" required value="<?php echo htmlspecialchars($faculty['last_name'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="suffix_modal" class="block text-sm font-medium text-gray-700 mb-1">Suffix (ex. Jr. Sr. III)</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="suffix_modal" name="suffix" value="<?php echo htmlspecialchars($faculty['suffix'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="title_modal" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" id="title_modal" name="title" value="<?php echo htmlspecialchars($faculty['title'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus" placeholder="e.g., Dr., Prof., Mr., Ms.">
                                    </div>
                                </div>
                                <div>
                                    <label for="email_modal" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        </div>
                                        <input type="email" id="email_modal" name="email" required value="<?php echo htmlspecialchars($faculty['email'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                    </div>
                                </div>
                                <div>
                                    <label for="phone_modal" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-phone text-gray-400"></i>
                                        </div>
                                        <input type="text" id="phone_modal" name="phone" value="<?php echo htmlspecialchars($faculty['phone'], ENT_QUOTES, 'UTF-8'); ?>" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                    </div>
                                </div>
                                <div>
                                    <label for="profile_picture" class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                    <div class="file-input-wrapper relative">
                                        <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100">
                                        <p class="text-xs text-gray-600 mt-2">Accepted formats: JPEG, PNG (max 2MB)</p>
                                    </div>
                                    <?php if (!empty($faculty['profile_picture'])): ?>
                                        <button type="submit" name="remove_profile_picture" value="1" class="mt-2 text-red-500 hover:text-red-700 text-sm" onclick="return confirm('Are you sure you want to remove your profile picture?');">Remove Picture</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Information -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center space-x-2">
                                <i class="fas fa-graduation-cap text-orange-500"></i>
                                <span>Academic Information</span>
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label for="academic_rank" class="block text-sm font-medium text-gray-700 mb-1">Academic Rank</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-graduation-cap text-gray-400"></i>
                                        </div>
                                        <select id="academic_rank" name="academic_rank" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                            <option value="">Select Academic Rank</option>
                                            <option value="Instructor" <?php echo $faculty['academic_rank'] === 'Instructor' ? 'selected' : ''; ?>>Instructor</option>
                                            <option value="Assistant Professor" <?php echo $faculty['academic_rank'] === 'Assistant Professor' ? 'selected' : ''; ?>>Assistant Professor</option>
                                            <option value="Associate Professor" <?php echo $faculty['academic_rank'] === 'Associate Professor' ? 'selected' : ''; ?>>Associate Professor</option>
                                            <option value="Professor" <?php echo $faculty['academic_rank'] === 'Professor' ? 'selected' : ''; ?>>Professor</option>
                                            <option value="Chair Professor" <?php echo $faculty['academic_rank'] === 'Chair Professor' ? 'selected' : ''; ?>>Chair Professor</option>
                                            <option value="Dean" <?php echo $faculty['academic_rank'] === 'Dean' ? 'selected' : ''; ?>>Dean</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="employment_type" class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-briefcase text-gray-400"></i>
                                        </div>
                                        <select id="employment_type" name="employment_type" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                            <option value="">Select Employment Type</option>
                                            <option value="Full-time" <?php echo $faculty['employment_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="Part-time" <?php echo $faculty['employment_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="Adjunct" <?php echo $faculty['employment_type'] === 'Adjunct' ? 'selected' : ''; ?>>Adjunct</option>
                                            <option value="Visiting" <?php echo $faculty['employment_type'] === 'Visiting' ? 'selected' : ''; ?>>Visiting</option>
                                            <option value="Emeritus" <?php echo $faculty['employment_type'] === 'Emeritus' ? 'selected' : ''; ?>>Emeritus</option>
                                            <option value="Contractual" <?php echo $faculty['employment_type'] === 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="classification" class="block text-sm font-medium text-gray-700 mb-1">Classification</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-tag text-gray-400"></i>
                                        </div>
                                        <select id="classification" name="classification" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                            <option value="">Select Classification</option>
                                            <option value="VSL" <?php echo $faculty['classification'] === 'VSL' ? 'selected' : ''; ?>>VSL</option>
                                            <option value="TL" <?php echo $faculty['classification'] === 'TL' ? 'selected' : ''; ?>>TL</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-8">
                        <button type="button" id="cancelModalBtn" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" class="btn-orange px-6 py-3 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 font-medium">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Specialization Modal -->
        <div id="addSpecializationModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md mx-4 transform modal-content scale-95">
                <div class="bg-purple-600 text-white p-6 rounded-t-xl flex items-center justify-between">
                    <h3 class="text-xl font-bold flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Add Subject Specialization
                    </h3>
                    <button id="closeSpecializationModalBtn" class="text-white hover:text-purple-200 focus:outline-none bg-white bg-opacity-10 hover:bg-opacity-20 rounded-full h-8 w-8 flex items-center justify-center transition-all duration-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="/faculty/profile" class="p-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="add_specialization" value="1">

                    <div class="space-y-4">
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700 mb-1">Course/Subject</label>
                            <select id="course_id" name="course_id" required class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                <option value="">Select a course</option>
                                <?php
                                foreach ($courses as $course) {
                                    echo "<option value=\"{$course['course_id']}\">{$course['course_code']} - {$course['course_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label for="expertise_level" class="block text-sm font-medium text-gray-700 mb-1">Expertise Level</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-star text-gray-400"></i>
                                </div>
                                <select id="expertise_level" name="expertise_level" class="pl-10 pr-4 py-3 w-full rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                    <option value="">Select Level</option>
                                    <option value="Beginner">Beginner</option>
                                    <option value="Intermediate" selected>Intermediate</option>
                                    <option value="Expert">Expert</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="specialization_notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (Optional)</label>
                            <textarea id="specialization_notes" name="notes" rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white shadow-sm input-focus" placeholder="Any additional information about your specialization..."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                        <button type="button" id="cancelSpecializationBtn" class="bg-gray-200 text-gray-700 px-5 py-3 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium">Cancel</button>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-3 rounded-lg shadow-md transition-all duration-200 font-medium">Add Specialization</button>
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

            // Profile Modal Functions
            function openProfileModal() {
                const modal = document.getElementById('editProfileModal');
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                setTimeout(() => modalContent.classList.add('scale-100'), 50);
                document.body.style.overflow = 'hidden';
            }

            function closeProfileModal() {
                const modal = document.getElementById('editProfileModal');
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }, 300);
            }

            // Specialization Modal Functions
            function openSpecializationModal() {
                const modal = document.getElementById('addSpecializationModal');
                const modalContent = modal.querySelector('.modal-content');
                modal.classList.remove('hidden');
                setTimeout(() => modalContent.classList.add('scale-100'), 50);
                document.body.style.overflow = 'hidden';
            }

            function closeSpecializationModal() {
                const modal = document.getElementById('addSpecializationModal');
                const modalContent = modal.querySelector('.modal-content');
                modalContent.classList.remove('scale-100');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    // Reset form
                    modal.querySelector('form').reset();
                }, 300);
            }

            // Event Listeners
            document.getElementById('editProfileBtn').addEventListener('click', openProfileModal);
            document.getElementById('closeModalBtn').addEventListener('click', closeProfileModal);
            document.getElementById('cancelModalBtn').addEventListener('click', closeProfileModal);

            // Specialization modal event listeners
            document.getElementById('addSpecializationBtn').addEventListener('click', openSpecializationModal);
            document.getElementById('addSpecializationBtn2').addEventListener('click', openSpecializationModal);
            document.getElementById('addFirstSpecialization')?.addEventListener('click', openSpecializationModal);
            document.getElementById('closeSpecializationModalBtn').addEventListener('click', closeSpecializationModal);
            document.getElementById('cancelSpecializationBtn').addEventListener('click', closeSpecializationModal);

            // Close modals when clicking outside
            document.getElementById('editProfileModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('editProfileModal')) closeProfileModal();
            });

            document.getElementById('addSpecializationModal').addEventListener('click', (e) => {
                if (e.target === document.getElementById('addSpecializationModal')) closeSpecializationModal();
            });

            // Close modals with Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (!document.getElementById('editProfileModal').classList.contains('hidden')) {
                        closeProfileModal();
                    }
                    if (!document.getElementById('addSpecializationModal').classList.contains('hidden')) {
                        closeSpecializationModal();
                    }
                }
            });

            // Form Validation
            const profileForm = document.querySelector('#editProfileModal form');
            profileForm.addEventListener('submit', (e) => {
                let isValid = true;

                // Validate required fields
                profileForm.querySelectorAll('input[required]').forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });

                // Email validation
                const emailInput = document.getElementById('email_modal');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    emailInput.classList.add('border-red-500');
                    isValid = false;
                } else {
                    emailInput.classList.remove('border-red-500');
                }

                // Phone validation (optional but if provided, should be valid)
                const phoneInput = document.getElementById('phone_modal');
                const phoneRegex = /^\d{10,12}$/;
                if (phoneInput.value.trim() && !phoneRegex.test(phoneInput.value.trim())) {
                    phoneInput.classList.add('border-red-500');
                    isValid = false;
                } else {
                    phoneInput.classList.remove('border-red-500');
                }

                if (!isValid) {
                    e.preventDefault();
                    showToast('Please fill in all required fields correctly.', 'bg-red-500');
                }
            });

            // Specialization form validation
            const specializationForm = document.querySelector('#addSpecializationModal form');
            specializationForm.addEventListener('submit', (e) => {
                const courseSelect = document.getElementById('course_id');
                const levelSelect = document.getElementById('expertise_level');
                let isValid = true;

                if (!courseSelect.value) {
                    courseSelect.classList.add('border-red-500');
                    isValid = false;
                } else {
                    courseSelect.classList.remove('border-red-500');
                }

                if (!levelSelect.value) {
                    levelSelect.classList.add('border-red-500');
                    isValid = false;
                } else {
                    levelSelect.classList.remove('border-red-500');
                }

                if (!isValid) {
                    e.preventDefault();
                    showToast('Please select both course and expertise level.', 'bg-red-500');
                }
            });

            // Real-time validation
            profileForm.querySelectorAll('input[required]').forEach(input => {
                input.addEventListener('input', () => {
                    if (input.value.trim()) {
                        input.classList.remove('border-red-500');
                    }
                });
            });

            document.getElementById('email_modal').addEventListener('input', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value.trim())) {
                    this.classList.remove('border-red-500');
                }
            });

            document.getElementById('phone_modal').addEventListener('input', function() {
                const phoneRegex = /^\d{10,12}$/;
                if (!this.value.trim() || phoneRegex.test(this.value.trim())) {
                    this.classList.remove('border-red-500');
                }
            });

            // File input validation
            const profileInput = document.getElementById('profile_picture');
            profileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const validTypes = ['image/jpeg', 'image/png'];
                    if (!validTypes.includes(file.type)) {
                        showToast('Only JPEG or PNG files are allowed.', 'bg-red-500');
                        this.value = '';
                        return;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        showToast('File size must be less than 2MB.', 'bg-red-500');
                        this.value = '';
                        return;
                    }
                }
            });

            // Specialization select validation
            document.getElementById('course_id').addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('border-red-500');
                }
            });

            document.getElementById('expertise_level').addEventListener('change', function() {
                if (this.value) {
                    this.classList.remove('border-red-500');
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