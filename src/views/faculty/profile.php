<?php
$pageTitle = "Faculty Profile";
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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

        .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.95);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
        }

        .input-icon input,
        .input-icon select {
            padding-left: 2.5rem;
            width: 100%;
        }

        .btn-gold {
            background-color: #D4AF37;
            color: white;
        }

        .btn-gold:hover {
            background-color: #b8972e;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
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

        .toast {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .toast-hidden {
            opacity: 0;
            transform: translateY(-20px);
        }

        @media (max-width: 640px) {
            .modal-content {
                max-width: 95vw;
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div class="container mx-auto px-4 py-8 max-w-7xl">

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div id="flashToast" class="toast bg-<?php echo $_SESSION['flash']['type'] === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['flash']['type'] === 'success' ? 'green' : 'red'; ?>-500 text-<?php echo $_SESSION['flash']['type'] === 'success' ? 'green' : 'red'; ?>-700 p-4 mb-6 rounded-r-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-<?php echo $_SESSION['flash']['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['flash']['message']); ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Main Profile Display -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Profile Header -->
                        <div class="bg-yellow-600 from-amber-400 to-yellow-600 px-6 py-8">
                            <div class="flex items-center space-x-6">
                                <div class="relative">
                                    <div class="h-24 w-24 rounded-full overflow-hidden shadow-lg border-4 border-white">
                                        <?php if (!empty($faculty['profile_picture'])): ?>
                                            <img id="profilePicturePreview" src="<?php echo htmlspecialchars($faculty['profile_picture']); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div id="profilePicturePlaceholder" class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-600 text-2xl font-bold">
                                                <?php echo strtoupper(substr($faculty['first_name'], 0, 1) . substr($faculty['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <label for="profile_picture" class="absolute -bottom-2 -right-2 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100 cursor-pointer transition-colors">
                                        <i class="fas fa-camera text-amber-500"></i>
                                        <span class="sr-only">Upload profile picture</span>
                                    </label>
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></h2>
                                    <p class="text-amber-100 font-medium"><?php echo htmlspecialchars($faculty['department_name'] ?? 'N/A'); ?></p>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <?php if (!empty($faculty['classification'])): ?>
                                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white"><?php echo htmlspecialchars($faculty['classification']); ?></span>
                                        <?php endif; ?>
                                        <span class="text-amber-100 text-sm">Faculty Member</span>
                                    </div>
                                </div>
                                <button type="button" onclick="openModal('editProfileModal')" class="px-4 py-2 btn-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 flex items-center">
                                    <i class="fas fa-edit mr-2"></i>Edit Profile
                                </button>
                            </div>
                            <p id="profile-picture-error" class="text-xs text-red-200 mt-2 hidden"></p>
                        </div>

                        <!-- Academic Information Form -->
                        <div class="p-6">
                            <form id="academicForm" method="POST" action="/faculty/profile" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <input type="hidden" name="update_academic" value="1">

                                <!-- Personal Information -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                                        <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Personal Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <p class=" pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <?php echo htmlspecialchars($faculty['first_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <p class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <?php echo htmlspecialchars($faculty['last_name']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <p class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <?php echo htmlspecialchars($faculty['email']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                </div>
                                                <p class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <?php echo htmlspecialchars($faculty['phone'] ?? ''); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Academic Information -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                                        <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        Academic Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="academic_rank" class="block text-sm font-medium text-gray-700">Academic Rank</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                </div>
                                                <select name="academic_rank" id="academic_rank" class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <option value="">Select Academic Rank</option>
                                                    <option value="Instructor" <?php echo $faculty['academic_rank'] == 'Instructor' ? 'selected' : ''; ?>>Instructor</option>
                                                    <option value="Assistant Professor" <?php echo $faculty['academic_rank'] == 'Assistant Professor' ? 'selected' : ''; ?>>Assistant Professor</option>
                                                    <option value="Associate Professor" <?php echo $faculty['academic_rank'] == 'Associate Professor' ? 'selected' : ''; ?>>Associate Professor</option>
                                                    <option value="Professor" <?php echo $faculty['academic_rank'] == 'Professor' ? 'selected' : ''; ?>>Professor</option>
                                                    <option value="Chair Professor" <?php echo $faculty['academic_rank'] == 'Chair Professor' ? 'selected' : ''; ?>>Chair Professor</option>
                                                    <option value="Dean" <?php echo $faculty['academic_rank'] == 'Dean' ? 'selected' : ''; ?>>Dean</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="employment_type" class="block text-sm font-medium text-gray-700">Employment Type</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                                    </svg>
                                                </div>
                                                <select name="employment_type" id="employment_type" class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <option value="">Select Employment Type</option>
                                                    <option value="Full-time" <?php echo $faculty['employment_type'] == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                                    <option value="Part-time" <?php echo $faculty['employment_type'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                                    <option value="Contractual" <?php echo $faculty['employment_type'] == 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label for="classification" class="block text-sm font-medium text-gray-700">Classification</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </div>
                                                <select name="classification" id="classification" class="pl-11 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-3 transition-colors">
                                                    <option value="">No Classification</option>
                                                    <option value="TL" <?php echo $faculty['classification'] == 'TL' ? 'selected' : ''; ?>>TL</option>
                                                    <option value="VSL" <?php echo $faculty['classification'] == 'VSL' ? 'selected' : ''; ?>>VSL</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <button type="button" onclick="window.location.reload()" class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Cancel
                                    </button>
                                    <button type="submit" id="saveAcademicButton" class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-yellow-500 from-amber-500 to-yellow-600 hover:from-amber-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 shadow-lg transform transition-all hover:scale-105">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Save Academic Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Stats Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-chart-bar text-amber-500 mr-2"></i>Quick Stats
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 rounded-md p-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-book text-white"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-600">Courses Assigned</p>
                                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($courseCount); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-green-50 rounded-md p-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-clock text-white"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-600">Teaching Hours</p>
                                        <p class="text-xl font-bold text-gray-900"><?php echo number_format($teachingHours, 1); ?> <span class="text-sm font-normal text-gray-500">hrs/wk</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-purple-50 rounded-md p-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-500 rounded-md flex items-center justify-center">
                                        <i class="fas fa-star text-white"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-600">Specializations</p>
                                        <p class="text-xl font-bold text-gray-900"><?php echo count($specializations); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-bolt text-amber-500 mr-2"></i>Quick Actions
                        </h3>
                        <div class="space-y-3">
                            <a href="/faculty/schedule" class="flex items-center p-3 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-calendar text-blue-600"></i>
                                </div>
                                <span class="ml-3">View Schedule</span>
                            </a>
                            <button type="button" onclick="openModal('addSpecializationModal')" class="flex items-center p-3 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 transition-colors w-full">
                                <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                                    <i class="fas fa-plus text-purple-600"></i>
                                </div>
                                <span class="ml-3">Add Specialization</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specializations Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
                <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-star text-amber-500 mr-2"></i>Your Specializations
                    </h2>
                    <button type="button" onclick="openModal('addSpecializationModal')" class="px-4 py-2 btn-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 flex items-center">
                        <i class="fas fa-plus mr-2"></i>Add Specialization
                    </button>
                </div>
                <div class="p-6">
                    <?php if (empty($specializations)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-star text-4xl mb-4"></i>
                            <p class="text-lg font-medium">No specializations yet.</p>
                            <p class="mt-2">Add your areas of expertise to help with course assignments.</p>
                            <button type="button" onclick="openModal('addSpecializationModal')" class="mt-4 px-4 py-2 btn-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                Add Your First Specialization
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($specializations as $spec): ?>
                                <div class="bg-gray-50 rounded-md border border-gray-200 p-4 hover:shadow-md transition-all">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                                                                                                echo $spec['expertise_level'] === 'Expert' ? 'bg-green-100 text-green-800' : ($spec['expertise_level'] === 'Intermediate' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                                                                                                                ?>">
                                            <?php echo htmlspecialchars($spec['expertise_level']); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($spec['course_code']); ?></h3>
                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($spec['course_name']); ?></p>
                                    <form method="POST" action="/faculty/profile" class="mt-3">
                                        <input type="hidden" name="specialization_id" value="<?php echo $spec['specialization_id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" name="delete_specialization" onclick="return confirm('Are you sure you want to remove this specialization?')" class="text-red-600 hover:text-red-700 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Profile Modal -->
            <div id="editProfileModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
                <div class="modal-content">
                    <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                        <h2 class="text-xl font-semibold">Edit Profile</h2>
                        <button onclick="closeModal('editProfileModal')" class="text-white hover:text-gray-300 text-2xl" aria-label="Close modal">&times;</button>
                    </div>
                    <div class="p-6">
                        <form id="profileForm" method="POST" action="/faculty/profile" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="update_profile" value="1">
                            <div class="space-y-4">
                                <div class="input-icon">
                                    <label for="profile_picture" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                    <i class="fas fa-camera"></i>
                                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png" class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                    <p id="profile-picture-error" class="text-xs text-red-600 mt-1 hidden"></p>
                                </div>
                                <div class="input-icon">
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($faculty['first_name']); ?>" required
                                        class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div class="input-icon">
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($faculty['last_name']); ?>" required
                                        class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div class="input-icon">
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($faculty['email']); ?>" required
                                        class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                </div>
                                <div class="input-icon">
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($faculty['phone'] ?? ''); ?>"
                                        class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="+63 912 345 6789">
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-4">
                                <button type="button" onclick="closeModal('editProfileModal')" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" id="saveProfileButton" class="px-4 py-2 btn-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Specialization Modal -->
            <div id="addSpecializationModal" class="modal fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
                <div class=" bg-white rounded-xl shadow-lg w-full max-w-2xl mx-4 transform modal-content scale-95">
                    <div class="bg-gray-800 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
                        <h2 class="text-xl font-semibold">Add New Specialization</h2>
                        <button onclick="closeModal('addSpecializationModal')" class="text-white hover:text-gray-300 text-2xl" aria-label="Close modal">&times;</button>
                    </div>
                    <div class="p-6">
                        <form id="specializationForm" method="POST" action="/faculty/profile">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="add_specialization" value="1">
                            <div class="space-y-4">
                                <div class="input-icon">
                                    <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                                    <i class="fas fa-book"></i>
                                    <select name="course_id" id="course_id" required class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <option value="">Select a course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?php echo $course['course_id']; ?>">
                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="input-icon">
                                    <label for="expertise_level" class="block text-sm font-medium text-gray-700">Expertise Level</label>
                                    <i class="fas fa-star"></i>
                                    <select name="expertise_level" id="expertise_level" class="mt-1 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                                        <option value="Beginner">Beginner - Basic knowledge</option>
                                        <option value="Intermediate" selected>Intermediate - Solid understanding</option>
                                        <option value="Expert">Expert - Advanced expertise</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-4">
                                <button type="button" onclick="closeModal('addSpecializationModal')" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 btn-gold text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                    Add Specialization
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            modal.querySelector('select, input').focus();
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Modal handling
            const modals = ['editProfileModal', 'addSpecializationModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(modalId);
                });
            });

            // Close modals on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    modals.forEach(closeModal);
                }
            });

            // Profile picture handling
            const profilePictureInput = document.getElementById('profile_picture');
            const profilePicturePreview = document.getElementById('profilePicturePreview');
            const profilePicturePlaceholder = document.getElementById('profilePicturePlaceholder');
            const errorElement = document.getElementById('profile-picture-error');

            profilePictureInput.addEventListener('change', function() {
                const file = this.files[0];
                errorElement.classList.add('hidden');

                if (file) {
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                        errorElement.textContent = 'Please select a JPEG or PNG image file.';
                        errorElement.classList.remove('hidden');
                        this.value = '';
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        errorElement.textContent = 'File size must be less than 2MB.';
                        errorElement.classList.remove('hidden');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (profilePicturePreview) {
                            profilePicturePreview.src = e.target.result;
                        } else if (profilePicturePlaceholder) {
                            profilePicturePlaceholder.outerHTML = `<img id="profilePicturePreview" src="${e.target.result}" alt="Profile Picture" class="w-full h-full object-cover">`;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Profile form validation
            const profileForm = document.getElementById('profileForm');
            const saveProfileButton = document.getElementById('saveProfileButton');
            profileForm.addEventListener('submit', function(e) {
                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const email = document.getElementById('email').value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!firstName || !lastName || !email) {
                    e.preventDefault();
                    errorElement.textContent = 'Please fill in all required fields.';
                    errorElement.classList.remove('hidden');
                    return;
                }

                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    errorElement.textContent = 'Please enter a valid email address.';
                    errorElement.classList.remove('hidden');
                    return;
                }

                saveProfileButton.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Saving...`;
                saveProfileButton.disabled = true;
            });

            // Academic form validation
            const academicForm = document.getElementById('academicForm');
            const saveAcademicButton = document.getElementById('saveAcademicButton');
            academicForm.addEventListener('submit', function(e) {
                const academicRank = document.getElementById('academic_rank').value;
                const employmentType = document.getElementById('employment_type').value;
                const classification = document.getElementById('classification').value;

                if (academicRank && !['Instructor', 'Assistant Professor', 'Associate Professor', 'Professor', 'Chair Professor', 'Dean'].includes(academicRank)) {
                    e.preventDefault();
                    errorElement.textContent = 'Invalid academic rank selected.';
                    errorElement.classList.remove('hidden');
                    return;
                }

                if (employmentType && !['Full-time', 'Part-time', 'Contractual'].includes(employmentType)) {
                    e.preventDefault();
                    errorElement.textContent = 'Invalid employment type selected.';
                    errorElement.classList.remove('hidden');
                    return;
                }

                if (classification && !['TL', 'VSL', ''].includes(classification)) {
                    e.preventDefault();
                    errorElement.textContent = 'Invalid classification selected.';
                    errorElement.classList.remove('hidden');
                    return;
                }

                saveAcademicButton.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Saving...`;
                saveAcademicButton.disabled = true;
            });

            // Auto-hide flash messages
            const flashToast = document.getElementById('flashToast');
            if (flashToast) {
                setTimeout(() => {
                    flashToast.classList.add('toast-hidden');
                    setTimeout(() => flashToast.remove(), 300);
                }, 5000);
            }
        });
    </script>
</body>

</html>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>