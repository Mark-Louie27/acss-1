<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRMSU Iba Campus Class Schedules - ACSS</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap');

        :root {
            --gold-primary: #DA9100;
            --gold-secondary: #FCC201;
            --gold-light: #FFEEAA;
            --gold-dark: #B8860B;
            --gold-gradient-start: #FFD200;
            --gold-gradient-end: #FFEEAA;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #FAFAFA;
            color: #333;
        }

        .hero-pattern {
            background: linear-gradient(135deg, var(--gold-primary), var(--gold-secondary));
            position: relative;
            overflow: hidden;
        }

        .hero-pattern::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.15'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: 0;
        }

        .form-input {
            @apply rounded-lg border-gray-300 focus:border-gold-500 focus:ring focus:ring-gold-200 focus:ring-opacity-50 transition duration-150;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-primary));
            @apply text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out shadow-md hover:shadow-lg focus:outline-none;
        }

        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .btn-secondary {
            @apply bg-white text-gold-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-lg transition shadow-md hover:shadow-lg;
        }

        .schedule-card {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .schedule-card:hover {
            transform: translateX(3px);
            border-left-color: var(--gold-primary);
            background-color: rgba(255, 238, 170, 0.1);
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--gold-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .gold-gradient-text {
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }

        .card-shadow {
            box-shadow: 0 10px 25px -5px rgba(212, 175, 55, 0.1), 0 8px 10px -6px rgba(212, 175, 55, 0.1);
        }

        .gold-border {
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .shimmer-effect {
            position: relative;
            overflow: hidden;
        }

        .shimmer-effect::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.3),
                    transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            100% {
                left: 150%;
            }
        }
    </style>
</head>

<body class="bg-white">
    <!-- Header -->
    <header class="hero-pattern shadow-lg fixed w-full z-20">
        <div class="container mx-auto px-6 py-6 relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <div class="mr-4">
                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-md">
                            <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" class="w-12 h-12" />
                        </div>
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-white">President Ramon Magsaysay State University</h1>
                        <p class="text-white text-sm md:text-base opacity-90">Academic Schedule Management System</p>
                    </div>
                </div>
                <nav class="flex space-x-3">
                    <a href="/auth/login" class="bg-white hover:bg-gray-50 text-gold-700 py-2 px-5 rounded-lg flex items-center transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        <span>Login</span>
                    </a>
                    <a href="/auth/register" class="btn-primary px-5 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        <span>Register</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-12">
        <!-- Hero Banner -->
        <div class="bg-white rounded-2xl shadow-xl mb-12 overflow-hidden card-shadow gold-border">
            <div class="flex flex-col md:flex-row">
                <div class="p-8 md:w-3/5 bg-gradient-to-r from-gold-primary to-gold-dark shimmer-effect">
                    <h2 class="text-3xl font-bold text-white mb-4">Find Your Class Schedule</h2>
                    <p class="text-white text-opacity-90 mb-8 leading-relaxed">Access comprehensive class schedules for all courses, departments, and instructors at PRMSU. Plan your academic journey efficiently.</p>
                    <div class="flex space-x-4">
                        <a href="#search-form" class="bg-white text-gold-700 hover:bg-gray-50 font-medium py-3 px-6 rounded-lg transition shadow-md hover:shadow-lg flex items-center">
                            <span>Search Now</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <div class="md:w-2/5 bg-white flex items-center justify-center p-8">
                    <img src="/assets/logo/main_logo/campus.jpg" alt="University Campus" class="rounded-lg shadow-lg object-cover w-full h-64" />
                </div>
            </div>
        </div>

        <!-- Search Filters -->
        <div id="search-form" class="bg-white rounded-xl shadow-lg p-8 mb-12 card-shadow gold-border">
            <h3 class="text-xl font-semibold mb-6 gold-gradient-text">Find Your Class Schedule</h3>
            <form id="searchForm" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- College Filter -->
                <div>
                    <label for="college" class="block text-sm font-medium text-gray-700 mb-2">College</label>
                    <div class="relative">
                        <select id="college" name="college_id" class="w-full form-input pl-10 py-3 border-2 border-yellow-500">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['college_id'] ?>"><?= $college['college_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-university text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Department Filter -->
                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <div class="relative">
                        <select id="department" name="department_id" class="w-full form-input pl-10 py-3 border-2 border-yellow-500">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= $department['department_id'] ?>"><?= $department['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-building text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Year Level Filter -->
                <div>
                    <label for="year_level" class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                    <div class="relative">
                        <select id="year_level" name="year_level" class="w-full form-input pl-10 py-3 border-2 border-yellow-500">
                            <option value="">All Levels</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-calendar-alt text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Section Filter -->
                <div>
                    <label for="section" class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                    <div class="relative">
                        <select id="section" name="section_id" class="w-full form-input pl-10 py-3 border-2 border-yellow-500">
                            <option value="">All Sections</option>
                        </select>
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-users text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="md:col-span-2">
                    <label for="global-search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" id="global-search" name="search"
                            placeholder="Search courses, instructors..."
                            class="w-full form-input pl-12 py-3 border-2 border-yellow-500">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                            <i class="fas fa-search text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Search Button -->
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full py-3 flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>
                        <span>Search Schedules</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Schedule Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Course
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Section
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Schedule
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Room
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Instructor
                        </th>
                    </tr>
                </thead>
                <tbody id="schedule-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Schedules will be populated dynamically -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700" id="pagination-info">
                        Showing <span class="font-medium">0</span> to <span class="font-medium">0</span> of <span class="font-medium">0</span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination" id="pagination-nav">
                        <!-- Pagination links will be populated dynamically -->
                    </nav>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
            <div class="bg-white p-6 rounded-xl shadow-lg text-center card-shadow gold-border">
                <div class="w-16 h-16 mx-auto mb-4 bg-gold-light rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-gold-dark text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Real-time Updates</h3>
                <p class="text-gray-600">Get instant notifications about schedule changes and announcements.</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg text-center card-shadow gold-border">
                <div class="w-16 h-16 mx-auto mb-4 bg-gold-light rounded-full flex items-center justify-center">
                    <i class="fas fa-mobile-alt text-gold-dark text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Mobile Friendly</h3>
                <p class="text-gray-600">Access your schedule anytime, anywhere on any device.</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg text-center card-shadow gold-border">
                <div class="w-16 h-16 mx-auto mb-4 bg-gold-light rounded-full flex items-center justify-center">
                    <i class="fas fa-sync-alt text-gold-dark text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Seamless Integration</h3>
                <p class="text-gray-600">Easily export to Google Calendar, Outlook, or download as PDF.</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 shadow-inner">
        <div class="container mx-auto px-6 py-10">
            <div class="flex flex-col md:flex-row justify-between">
                <div class="mb-6 md:mb-0">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center mr-3">
                            <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" class="w-8 h-8" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">PRMSU</h2>
                            <p class="text-sm text-gray-400">Academic Schedule Management System</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm max-w-md">
                        President Ramon Magsaysay State University is dedicated to providing quality education
                        through advanced technological solutions.
                    </p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-gold-primary font-semibold mb-3">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Home</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">About</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Courses</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Faculty</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-gold-primary font-semibold mb-3">Resources</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Student Portal</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">Library</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">E-Learning</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-gold-primary font-semibold mb-3">Contact</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center text-gray-400">
                                <i class="fas fa-map-marker-alt mr-2 text-gold-primary"></i>
                                Iba, Zambales, Philippines
                            </li>
                            <li class="flex items-center text-gray-400">
                                <i class="fas fa-phone mr-2 text-gold-primary"></i>
                                +63 (XXX) XXX-XXXX
                            </li>
                            <li class="flex items-center text-gray-400">
                                <i class="fas fa-envelope mr-2 text-gold-primary"></i>
                                info@prmsu.edu.ph
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-400">
                    © 2025 President Ramon Magsaysay State University. All rights reserved.
                </p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-gold-primary transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-primary transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-primary transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-primary transition">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial schedules
            fetchSchedules();

            // College change handler
            document.getElementById('college').addEventListener('change', function() {
                const collegeId = this.value;
                const departmentSelect = document.getElementById('department');
                const sectionSelect = document.getElementById('section');

                if (collegeId) {
                    fetch(`/public/departments?college_id=${collegeId}`)
                        .then(response => response.json())
                        .then(departments => {
                            departmentSelect.innerHTML = '<option value="">All Departments</option>';
                            departments.forEach(dept => {
                                const option = new Option(dept.department_name, dept.department_id);
                                departmentSelect.add(option);
                            });
                            sectionSelect.innerHTML = '<option value="">All Sections</option>';
                        })
                        .catch(error => console.error('Error fetching departments:', error));
                } else {
                    departmentSelect.innerHTML = '<option value="">All Departments</option>';
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                }
            });

            // Department change handler
            document.getElementById('department').addEventListener('change', function() {
                const deptId = this.value;
                const sectionSelect = document.getElementById('section');

                if (deptId) {
                    fetch(`/public/sections?department_id=${deptId}`)
                        .then(response => response.json())
                        .then(sections => {
                            sectionSelect.innerHTML = '<option value="">All Sections</option>';
                            sections.forEach(section => {
                                const option = new Option(section.section_name, section.section_id);
                                sectionSelect.add(option);
                            });
                        })
                        .catch(error => console.error('Error fetching sections:', error));
                } else {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                }
            });

            // Form submission handler
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                fetchSchedules();
            });
        });

        function fetchSchedules(page = 1) {
            const formData = new FormData(document.getElementById('searchForm'));
            formData.append('page', page);

            fetch('/public/search', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error:', data.error);
                        updateScheduleResults([]);
                        updatePagination(0, 0);
                    } else {
                        updateScheduleResults(data.schedules);
                        updatePagination(data.total, data.page, data.per_page);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    updateScheduleResults([]);
                    updatePagination(0, 0);
                });
        }

        function updateScheduleResults(schedules) {
            const tbody = document.getElementById('schedule-table-body');
            tbody.innerHTML = '';

            if (schedules.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No schedules found.
                        </td>
                    </tr>
                `;
                return;
            }

            schedules.forEach(schedule => {
                const row = `
                    <tr class="hover:bg-yellow-50 transition-colors schedule-card">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gold-primary">${schedule.course_code}</div>
                            <div class="text-sm text-gray-500">${schedule.course_name}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            ${schedule.section_name}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <span class="px-2 py-1 rounded-full bg-gold-light text-gold-dark text-xs">
                                ${schedule.day_of_week} ${schedule.start_time} - ${schedule.end_time}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            ${schedule.room_name ? schedule.room_name + ' (' + schedule.building + ')' : 'TBA'}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            ${schedule.instructor_name}
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        }

        function updatePagination(total, currentPage, perPage) {
            const paginationInfo = document.getElementById('pagination-info');
            const paginationNav = document.getElementById('pagination-nav');
            const totalPages = Math.ceil(total / perPage);

            // Update pagination info
            const start = (currentPage - 1) * perPage + 1;
            const end = Math.min(currentPage * perPage, total);
            paginationInfo.innerHTML = `
                Showing <span class="font-medium">${start}</span> to <span class="font-medium">${end}</span> of <span class="font-medium">${total}</span> results
            `;

            // Update pagination navigation
            paginationNav.innerHTML = '';

            // Previous button
            paginationNav.innerHTML += `
                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === 1 ? 'cursor-not-allowed' : ''}" 
                   ${currentPage > 1 ? `onclick="fetchSchedules(${currentPage - 1})"` : ''}>
                    <span class="sr-only">Previous</span>
                    <i class="fas fa-chevron-left"></i>
                </a>
            `;

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationNav.innerHTML += `
                    <a href="#" class="${i === currentPage ? 'z-10 bg-gold-primary text-white border-gold-primary' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'} relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                       onclick="fetchSchedules(${i})">
                        ${i}
                    </a>
                `;
            }

            // Next button
            paginationNav.innerHTML += `
                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 ${currentPage === totalPages ? 'cursor-not-allowed' : ''}" 
                   ${currentPage < totalPages ? `onclick="fetchSchedules(${currentPage + 1})"` : ''}>
                    <span class="sr-only">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            `;
        }
    </script>
</body>

</html>