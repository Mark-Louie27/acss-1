<?php
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Colleges & Departments</h1>

    <!-- Toast Notifications -->
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('<?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); ?>', 'success');
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast('<?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?>', 'error');
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button id="college-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm active-tab" data-tab="college">
                    Colleges
                </button>
                <button id="department-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="department">
                    Departments
                </button>
            </nav>
        </div>
    </div>

    <!-- College Section -->
    <div id="college-content" class="tab-content">
        <!-- Create College Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6 card">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New College</h2>
            <form action="/admin/colleges_departments/create" method="POST" class="space-y-4">
                <input type="hidden" name="type" value="college">
                <div>
                    <label for="college_name" class="block text-gray-600">College Name</label>
                    <input type="text" id="college_name" name="college_name" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="e.g., College of Engineering">
                </div>
                <div>
                    <label for="college_code" class="block text-gray-600">College Code</label>
                    <input type="text" id="college_code" name="college_code" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="e.g., COE">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 btn">Create College</button>
            </form>
        </div>
        <!-- Colleges Table -->
        <div class="bg-white p-6 rounded-lg shadow-md card">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Colleges List</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 text-left">Name</th>
                        <th class="p-2 text-left">Code</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($colleges)): ?>
                        <tr>
                            <td colspan="2" class="p-2 text-center text-gray-500">No colleges found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($colleges as $college): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-2"><?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($college['college_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Department Section -->
    <div id="department-content" class="tab-content hidden">
        <!-- Create Department Form -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6 card">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New Department</h2>
            <form action="/admin/colleges_departments/create" method="POST" class="space-y-4">
                <input type="hidden" name="type" value="department">
                <div>
                    <label for="department_name" class="block text-gray-600">Department Name</label>
                    <input type="text" id="department_name" name="department_name" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" placeholder="e.g., Computer Science">
                </div>
                <div>
                    <label for="college_id" class="block text-gray-600">College</label>
                    <select id="college_id" name="college_id" required class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                        <option value="" disabled selected>Select a college</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo htmlspecialchars($college['college_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 btn">Create Department</button>
            </form>
        </div>
        <!-- Departments Table -->
        <div class="bg-white p-6 rounded-lg shadow-md card">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Departments List</h2>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2 text-left">Name</th>
                        <th class="p-2 text-left">College</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="2" class="p-2 text-center text-gray-500">No departments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $department): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="p-2"><?php echo htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($department['college_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active state from all buttons and contents
                tabButtons.forEach(btn => {
                    btn.classList.remove('border-blue-500', 'text-blue-600', 'active-tab');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                tabContents.forEach(content => content.classList.add('hidden'));

                // Add active state to clicked button and show corresponding content
                button.classList.add('border-blue-500', 'text-blue-600', 'active-tab');
                button.classList.remove('border-transparent', 'text-gray-500');
                document.getElementById(`${button.dataset.tab}-content`).classList.remove('hidden');
            });
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>