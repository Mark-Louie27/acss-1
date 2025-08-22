<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }
        }

        .card {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease;
        }

        .profile-pic {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }

        .modal {
            transition: opacity 0.3s ease;
        }

        .modal-content {
            transition: transform 0.3s ease;
        }

        .input-focus:focus {
            border-color: #4b5e97;
            outline: none;
            box-shadow: 0 0 0 3px rgba(75, 94, 151, 0.2);
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">

    <!-- Main Content -->
    <div class="ml-64 p-6 min-h-screen">
        <!-- Mobile Menu Toggle -->
        <button id="menuToggle" class="md:hidden fixed top-4 left-4 z-50 bg-indigo-600 text-white p-2 rounded">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <!-- Profile Section -->
        <div class="max-w-4xl mx-auto">
            <div class="card p-6">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Profile Picture -->
                    <div class="w-40 h-40 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($data['user']['profile_picture'] ?? '/default-avatar.png'); ?>" alt="Profile Picture" class="profile-pic w-full h-full">
                    </div>
                    <!-- Form -->
                    <form method="POST" enctype="multipart/form-data" class="w-full">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700">First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($data['user']['first_name'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Middle Name</label>
                                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($data['user']['middle_name'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($data['user']['last_name'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Suffix</label>
                                <input type="text" name="suffix" value="<?php echo htmlspecialchars($data['user']['suffix'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($data['user']['phone'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Title</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($data['user']['title'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Employment Type</label>
                                <select name="employment_type" class="w-full mt-1 p-2 border rounded input-focus">
                                    <option value="" <?php echo (!isset($data['user']['employment_type']) || $data['user']['employment_type'] === '') ? 'selected' : ''; ?>>Select Type</option>
                                    <option value="Full-time" <?php echo (isset($data['user']['employment_type']) && $data['user']['employment_type'] === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="Part-time" <?php echo (isset($data['user']['employment_type']) && $data['user']['employment_type'] === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700">Course Specialization</label>
                                <select name="course_specialization" class="w-full mt-1 p-2 border rounded input-focus">
                                    <option value="" <?php echo (!isset($data['user']['course_specialization']) || $data['user']['course_specialization'] === '') ? 'selected' : ''; ?>>Select Specialization</option>
                                    <select id="course_id" name="course_id" required class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white shadow-sm input-focus">
                                        <option value="">Select a course</option>
                                        <?php
                                        $stmt = $this->db->prepare("SELECT course_id, course_code, course_name FROM courses WHERE is_active = 1 ORDER BY course_code");
                                        $stmt->execute();
                                        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($courses as $course) {
                                            echo "<option value=\"{$course['course_id']}\">{$course['course_code']} - {$course['course_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </select>
                                <select name="expertise_level" class="w-full mt-2 p-2 border rounded input-focus">
                                    <option value="Beginner" <?php echo (isset($data['user']['expertise_level']) && $data['user']['expertise_level'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="Intermediate" <?php echo (!isset($data['user']['expertise_level']) || $data['user']['expertise_level'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="Expert" <?php echo (isset($data['user']['expertise_level']) && $data['user']['expertise_level'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700">Academic Rank</label>
                                <input type="text" name="academic_rank" value="<?php echo htmlspecialchars($data['user']['academic_rank'] ?? ''); ?>" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                            <div>
                                <label class="block text-gray-700">Profile Picture</label>
                                <input type="file" name="profile_picture" accept="image/*" class="w-full mt-1 p-2 border rounded input-focus">
                            </div>
                        </div>
                        <button type="submit" class="mt-4 bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>