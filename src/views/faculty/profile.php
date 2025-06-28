<?php
$pageTitle = "Faculty Profile";
ob_start();
?>

<div class="min-h-screen bg-light-gray">
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?> flex items-start">
                <div class="flex-shrink-0">
                    <?php echo $_SESSION['flash']['type'] === 'success' ? '<i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>' : '<i class="fas fa-exclamation-circle text-red-500 mr-3 mt-1"></i>'; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['flash']['message']); ?></p>
                </div>
                <button type="button" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-lg inline-flex items-center justify-center h-8 w-8 <?php echo $_SESSION['flash']['type'] === 'success' ? 'text-green-500 hover:bg-green-100' : 'text-red-500 hover:bg-red-100'; ?>" onclick="this.parentElement.remove()">
                    <span class="sr-only">Dismiss</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Profile Card -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="relative flex-shrink-0 h-20 w-20 rounded-full overflow-hidden">
                        <?php if (!empty($faculty['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($faculty['profile_picture']); ?>" alt="Profile Picture" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full rounded-full bg-gold flex items-center justify-center text-dark-gray text-2xl font-bold">
                                <?php echo strtoupper(substr($faculty['first_name'], 0, 1) . substr($faculty['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-dark-gray"><?php echo htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($faculty['department_name'] ?? 'N/A'); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($faculty['classification'] ?? 'No Classification'); ?></p>
                    </div>
                </div>

                <form method="POST" action="/faculty/profile" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="profile_picture" class="block text-sm font-medium text-dark-gray mb-1">Profile Picture</label>
                            <div class="relative border-2 border-dashed border-gray-300 rounded-md p-4 text-center hover:border-gold">
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div id="preview" class="flex items-center justify-center">
                                    <i class="fas fa-upload text-gold text-2xl"></i>
                                    <span class="ml-2 text-sm text-gray-600">Drop or click to upload (JPEG/PNG, max 2MB)</span>
                                </div>
                            </div>
                            <?php if (!empty($faculty['profile_picture'])): ?>
                                <button type="submit" name="remove_profile_picture" class="mt-2 text-sm text-red-600 hover:text-red-700 flex items-center">
                                    <i class="fas fa-trash mr-1"></i> Remove Picture
                                </button>
                            <?php endif; ?>
                            <p id="profile-picture-error" class="text-xs text-red-600 hidden mt-1"></p>
                        </div>
                        <div>
                            <label for="classification" class="block text-sm font-medium text-dark-gray mb-1">Classification</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-tags text-gray-400"></i>
                                </div>
                                <select name="classification" id="classification" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2">
                                    <option value="">No Classification</option>
                                    <option value="TL" <?php echo $faculty['classification'] == 'TL' ? 'selected' : ''; ?>>TL</option>
                                    <option value="VSL" <?php echo $faculty['classification'] == 'VSL' ? 'selected' : ''; ?>>VSL</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-dark-gray mb-1">First Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($faculty['first_name']); ?>" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2" required>
                            </div>
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-dark-gray mb-1">Last Name</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($faculty['last_name']); ?>" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2" required>
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-dark-gray mb-1">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($faculty['email']); ?>" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2" required>
                            </div>
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-dark-gray mb-1">Phone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($faculty['phone'] ?? ''); ?>" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2" placeholder="+63 912 345 6789">
                            </div>
                        </div>
                        <div>
                            <label for="academic_rank" class="block text-sm font-medium text-dark-gray mb-1">Academic Rank</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-briefcase text-gray-400"></i>
                                </div>
                                <input type="text" id="academic_rank" value="<?php echo htmlspecialchars($faculty['academic_rank']); ?>" class="pl-10 block w-full rounded-md border-gray-300 bg-gray-50 sm:text-sm py-2" readonly>
                            </div>
                        </div>
                        <div>
                            <label for="employment_type" class="block text-sm font-medium text-dark-gray mb-1">Employment Type</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-id-card text-gray-400"></i>
                                </div>
                                <input type="text" id="employment_type" value="<?php echo htmlspecialchars($faculty['employment_type']); ?>" class="pl-10 block w-full rounded-md border-gray-300 bg-gray-50 sm:text-sm py-2" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4">
                        <button type="submit" name="update_profile" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gold hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold">
                            <i class="fas fa-save mr-2"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Stats Card -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-dark-gray mb-4 flex items-center">
                    <i class="fas fa-chart-pie text-gold mr-2"></i> Quick Stats
                </h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-gold mr-3">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Courses Assigned</p>
                                <p class="text-lg font-semibold text-dark-gray"><?php echo htmlspecialchars($courseCount); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-gold mr-3">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Teaching Hours</p>
                                <p class="text-lg font-semibold text-dark-gray"><?php echo number_format($teachingHours, 1); ?> hrs/wk</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-gold mr-3">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Specializations</p>
                                <p class="text-lg font-semibold text-dark-gray"><?php echo count($specializations); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="/faculty/schedule" class="text-sm font-medium text-gold hover:text-yellow-600 flex items-center">
                        <i class="fas fa-calendar-alt mr-2"></i> View Teaching Schedule
                    </a>
                </div>
            </div>
        </div>

        <!-- Specializations Section -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-dark-gray flex items-center">
                    <i class="fas fa-graduation-cap text-gold mr-2"></i> Your Specializations
                </h2>
                <button type="button" onclick="document.getElementById('addSpecializationModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-dark-gray bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold">
                    <i class="fas fa-plus mr-2"></i> Add Specialization
                </button>
            </div>

            <?php if (empty($specializations)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-graduation-cap text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-dark-gray">No specializations yet</h3>
                    <p class="text-sm text-gray-500">Add your areas of expertise to help with course assignments.</p>
                    <div class="mt-6">
                        <button type="button" onclick="document.getElementById('addSpecializationModal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-dark-gray bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold">
                            <i class="fas fa-plus mr-2"></i> Add Your First Specialization
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($specializations as $spec): ?>
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden transition-all hover:shadow-lg">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center text-gold">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-dark-gray"><?php echo htmlspecialchars($spec['course_code'] . ' - ' . $spec['course_name']); ?></h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                                                                                                                    echo $spec['expertise_level'] === 'Expert' ? 'bg-green-100 text-green-800' : ($spec['expertise_level'] === 'Intermediate' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800');
                                                                                                                                    ?>">
                                                <?php echo htmlspecialchars($spec['expertise_level']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 flex justify-end space-x-2">
                                <form method="POST" action="/faculty/profile" class="inline">
                                    <input type="hidden" name="specialization_id" value="<?php echo $spec['specialization_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <button type="submit" name="delete_specialization" onclick="return confirm('Are you sure you want to delete this specialization?')" class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Add Specialization Modal -->
        <div id="addSpecializationModal" class="hidden fixed inset-0 overflow-y-auto z-50">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true"></span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-graduation-cap text-gold"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-dark-gray">Add New Specialization</h3>
                                <div class="mt-4">
                                    <form method="POST" action="/faculty/profile" id="specializationForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label for="course_id" class="block text-sm font-medium text-dark-gray">Course</label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-book text-gray-400"></i>
                                                    </div>
                                                    <select name="course_id" id="course_id" class="pl-10 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2" required>
                                                        <option value="">Select a course</option>
                                                        <?php foreach ($courses as $course): ?>
                                                            <option value="<?php echo $course['course_id']; ?>">
                                                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="expertise_level" class="block text-sm font-medium text-dark-gray">Expertise Level</label>
                                                <select name="expertise_level" id="expertise_level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold focus:ring-gold sm:text-sm py-2">
                                                    <option value="Beginner">Beginner</option>
                                                    <option value="Intermediate" selected>Intermediate</option>
                                                    <option value="Expert">Expert</option>
                                                </select>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" form="specializationForm" name="add_specialization" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white bg-gold hover:bg-yellow-600 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-plus mr-2"></i> Add Specialization
                        </button>
                        <button type="button" onclick="document.getElementById('addSpecializationModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-dark-gray hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('addSpecializationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        const profilePictureInput = document.getElementById('profile_picture');
        const preview = document.getElementById('preview');
        const errorElement = document.getElementById('profile-picture-error');

        profilePictureInput.addEventListener('change', function() {
            const file = this.files[0];
            errorElement.classList.add('hidden');
            preview.innerHTML = '<i class="fas fa-upload text-gold text-2xl"></i><span class="ml-2 text-sm text-gray-600">Drop or click to upload (JPEG/PNG, max 2MB)</span>';

            if (file) {
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    errorElement.textContent = 'Only JPEG and PNG files are allowed.';
                    errorElement.classList.remove('hidden');
                    this.value = '';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    errorElement.textContent = 'File size exceeds 2MB limit.';
                    errorElement.classList.remove('hidden');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-24 h-24 object-cover rounded-md">`;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

    <?php
    $content = ob_get_clean();
    require_once __DIR__ . '/layout.php';
    ?>
